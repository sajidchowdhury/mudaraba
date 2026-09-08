<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

/**
 * Auditable — opt-in trait for models whose mutations should be
 * written to the audit_logs table.
 *
 * Usage:
 *   class InvestmentTransaction extends Model {
 *       use Auditable, SoftDeletes, ...;
 *   }
 *
 * The trait wires up Eloquent's `created` / `updating` / `updated` /
 * `deleted` events automatically. No need to register Observers in
 * AppServiceProvider — `bootAuditable()` is called by Laravel when
 * the trait is used on a model.
 *
 * Models may override `shouldAudit(string $action, $model): bool`
 * to suppress specific actions. Useful for high-frequency internal
 * updates (e.g. InvestorMonthlyProfitDetail skips 'delete' events
 * because the ProfitCalculatorService bulk-deletes during re-finalize;
 * those deletes are plumbing, not user intent).
 *
 * Models may override `auditAttributes()` to control what gets
 * captured in before_data / after_data. By default, all attributes
 * except created_at, updated_at, deleted_at, and batch_uuid.
 */
trait Auditable
{
    /**
     * Boot the trait — wire up the Eloquent event listeners.
     *
     * Called automatically by Laravel when the trait is used.
     */
    public static function bootAuditable(): void
    {
        // CREATE — capture the new model's attributes as `after_data`.
        static::created(function ($model) {
            if (static::shouldAudit(AuditService::ACTION_CREATE, $model)) {
                AuditService::log(
                    action: AuditService::ACTION_CREATE,
                    model: $model,
                    before: null,
                    after: AuditService::snapshot($model),
                );
            }
        });

        // UPDATE — snapshot before, snapshot after, diff, log only the changed keys.
        //
        // We hook `updating` (fires BEFORE the save) to:
        //   1. Set `updated_by` = Auth::id() (plan §4.1: universal updated_by)
        //   2. Capture the original attributes for the diff
        //
        // Then `updated` (fires AFTER) to capture the new ones and log.
        //
        // The before-snapshot is stashed in a static array keyed by the
        // model object's spl_object_id, then retrieved + unset in `updated`.
        // This avoids polluting the model's attributes (which Eloquent would
        // try to save as DB columns).
        static::updating(function ($model) {
            // Auto-set updated_by = current authenticated user (plan §4.1)
            if (Auth::id() && static::hasColumn($model, 'updated_by')) {
                $model->updated_by = Auth::id();
            }

            $originalModel = (new static())->setRawAttributes($model->getOriginal());
            static::$auditBeforeSnapshots[spl_object_id($model)] = AuditService::snapshot($originalModel);
        });

        static::updated(function ($model) {
            $objectId = spl_object_id($model);
            $before = static::$auditBeforeSnapshots[$objectId] ?? [];
            unset(static::$auditBeforeSnapshots[$objectId]);

            if (! static::shouldAudit(AuditService::ACTION_UPDATE, $model)) {
                return;
            }

            $after = AuditService::snapshot($model);
            [$beforeDiff, $afterDiff] = AuditService::diff($before, $after);

            // No-op if nothing changed (e.g. save() called without modifications)
            if (empty($beforeDiff) && empty($afterDiff)) {
                return;
            }

            AuditService::log(
                action: AuditService::ACTION_UPDATE,
                model: $model,
                before: $beforeDiff,
                after: $afterDiff,
            );
        });

        // DELETE — set deleted_by BEFORE the soft-delete fires, then log
        // the model's final state as `before_data`.
        //
        // We hook `deleting` (fires BEFORE the delete/soft-delete executes)
        // to set `deleted_by = Auth::id()` (plan §4.1: universal deleted_by).
        // This ensures the deleted_by column is saved in the same query as
        // the deleted_at column.
        static::deleting(function ($model) {
            // Auto-set deleted_by = current authenticated user (plan §4.1)
            if (Auth::id() && static::hasColumn($model, 'deleted_by')) {
                $model->deleted_by = Auth::id();
            }
        });

        static::deleted(function ($model) {
            if (! static::shouldAudit(AuditService::ACTION_DELETE, $model)) {
                return;
            }

            AuditService::log(
                action: AuditService::ACTION_DELETE,
                model: $model,
                before: AuditService::snapshot($model),
                after: null,
            );
        });
    }

    /**
     * Static array for stashing before-update snapshots, keyed by
     * the model instance's spl_object_id. Cleared in the `updated`
     * hook to avoid memory leaks.
     *
     * @var array<int, array<string, mixed>>
     */
    protected static array $auditBeforeSnapshots = [];

    /**
     * Check if the model's table has a specific column.
     * Used to safely check for optional columns like updated_by / deleted_by
     * before setting them (avoids "column not found" errors on tables that
     * don't have these columns).
     */
    protected static function hasColumn($model, string $column): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn(
                $model->getTable(),
                $column
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Models can override this to suppress specific actions from being
     * audited. Return false to skip auditing.
     *
     * Default implementation audits every create/update/delete.
     *
     * @param  string  $action  One of AuditService::ACTION_*
     */
    protected static function shouldAudit(string $action, $model): bool
    {
        return true;
    }
}
