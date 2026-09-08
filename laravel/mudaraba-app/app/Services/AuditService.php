<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * AuditService — writes audit log entries for model mutations.
 *
 * This service is called by model Observers (registered in
 * AppServiceProvider::boot) on every create/update/delete of a financial
 * model. It captures:
 *   - the acting user (auth()->id() at the time of mutation)
 *   - the action (create / update / delete / finalize / reconcile)
 *   - the entity type + id
 *   - the before/after state as JSONB (for updates; before only for deletes;
 *     after only for creates)
 *   - the request IP and user agent (best-effort — null in CLI/test contexts)
 *
 * Audit logs are append-only (the AuditLog model sets UPDATED_AT = null).
 *
 * === Design decisions ===
 *
 * 1. Why a service (not a trait that writes directly)?
 *    Trait methods can't easily be mocked in tests. A service lets us
 *    write `AuditService::log(...)` directly from anywhere (e.g. the
 *    ProfitCalculatorService can log a 'reconcile' action that doesn't
 *    map to a single Eloquent event) AND lets observers call it.
 *
 * 2. Why a separate Auditable trait (see app/Traits/Auditable.php)?
 *    The trait is the opt-in marker on a model: "this model should be
 *    audited". Models with the trait get auto-observed; models without
 *    it don't. This is cleaner than maintaining a giant array of model
 *    classes in AppServiceProvider.
 *
 * 3. Why capture IP + user agent?
 *    The plan §4.2 audit_logs schema explicitly includes both columns.
 *    For finance, knowing "who did this from what IP" is a standard
 *    audit requirement. In CLI / test contexts, Request::ip() returns
 *    null, so the columns will be null — which is fine.
 */
class AuditService
{
    /**
     * Standard actions used as the `action` column value.
     * Keep these short — they're indexed in audit_logs.action (VARCHAR 50).
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_FINALIZE = 'finalize';
    public const ACTION_RECONCILE = 'reconcile';
    public const ACTION_LOCK = 'lock';
    public const ACTION_UNLOCK = 'unlock';

    /**
     * Write a single audit log entry.
     *
     * @param  string  $action       One of the ACTION_* constants.
     * @param  Model  $model        The mutated Eloquent model.
     * @param  array|null  $before  Pre-mutation attributes (null for create).
     * @param  array|null  $after   Post-mutation attributes (null for delete).
     * @param  int|null  $userId    Override the acting user (defaults to auth()->id()).
     */
    public static function log(
        string $action,
        Model $model,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
    ): AuditLog {
        $userId ??= Auth::id();

        // Handle non-numeric primary keys (e.g. MonthlyProfitSummary has
        // profit_month as a string date PK, but audit_logs.entity_id is
        // unsignedBigInteger). For non-numeric keys, we set entity_id to
        // null and stash the actual key in after_data['_entity_key'] so
        // the audit row is still traceable. Numeric keys go to entity_id
        // directly for fast indexed lookups.
        $key = $model->getKey();
        $entityId = null;
        if (is_numeric($key)) {
            $entityId = (int) $key;
        } elseif ($key !== null) {
            // Preserve the actual key in after_data so the row is still
            // traceable. Merge with any existing after_data.
            $after = array_merge($after ?? [], ['_entity_key' => (string) $key]);
        }

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $model->getMorphClass(),
            'entity_id' => $entityId,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => self::requestIp(),
            'user_agent' => self::userAgent(),
        ]);
    }

    /**
     * Snapshot a model's audit-relevant attributes for storage in
     * before_data / after_data.
     *
     * By default, we capture:
     *   - all attributes EXCEPT: timestamps (created_at, updated_at,
     *     deleted_at) and batch_uuid (internal plumbing, not user data)
     *
     * Models can override this by defining a `auditAttributes()` method
     * returning a key => value array.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(Model $model): array
    {
        if (method_exists($model, 'auditAttributes')) {
            return $model->auditAttributes();
        }

        $attributes = $model->getAttributes();
        unset(
            $attributes['created_at'],
            $attributes['updated_at'],
            $attributes['deleted_at'],
            $attributes['batch_uuid'],
        );

        // Cast decimal/enum values to their readable form so the audit
        // log is human-readable, not raw DB values.
        foreach ($attributes as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            $attributes[$key] = self::castForAudit($model, $key, $value);
        }

        return $attributes;
    }

    /**
     * Diff old vs new attributes — used for updates.
     * Returns [before, after] arrays containing ONLY changed keys.
     *
     * @param  array  $old  Pre-mutation snapshot (from self::snapshot)
     * @param  array  $new  Post-mutation snapshot
     * @return array{0: array, 1: array}  [before_diff, after_diff]
     */
    public static function diff(array $old, array $new): array
    {
        $before = [];
        $after = [];

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        foreach ($keys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            if (self::valuesDiffer($oldVal, $newVal)) {
                $before[$key] = $oldVal;
                $after[$key] = $newVal;
            }
        }

        return [$before, $after];
    }

    /**
     * Best-effort request IP capture. Returns null in CLI / tests.
     */
    public static function requestIp(): ?string
    {
        try {
            $ip = Request::ip();
            return $ip === null || $ip === '127.0.0.1' && app()->runningUnitTests()
                ? null
                : $ip;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Best-effort user-agent capture. Returns null in CLI / tests.
     */
    public static function userAgent(): ?string
    {
        try {
            $ua = Request::userAgent();
            return $ua && strlen($ua) > 0 ? $ua : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Apply a model's cast to a value so the audit log shows readable data.
     */
    private static function castForAudit(Model $model, string $key, mixed $value): mixed
    {
        $casts = $model->getCasts();
        if (! isset($casts[$key])) {
            return $value;
        }

        $cast = $casts[$key];

        // Enums → their string value
        if (class_exists($cast) && enum_exists($cast) && $value instanceof \UnitEnum) {
            return $value->value ?? $value->name;
        }

        // decimal:2 → float
        if (str_starts_with($cast, 'decimal')) {
            return (float) $value;
        }

        // boolean → bool
        if ($cast === 'boolean') {
            return (bool) $value;
        }

        // date/datetime → ISO string
        if (in_array($cast, ['date', 'datetime', 'datetime:H:i:s'], true)) {
            try {
                return $model->$key?->toDateString() ?? $value;
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Compare two values for the diff. Loose comparison handles
     * string-vs-int issues with decimal casts.
     */
    private static function valuesDiffer(mixed $a, mixed $b): bool
    {
        if (is_null($a) && is_null($b)) {
            return false;
        }
        if (is_null($a) || is_null($b)) {
            return true;
        }

        // For numeric strings/decimals, compare as floats to avoid
        // "1.00" !== "1" false positives
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) >= 0.005;
        }

        return (string) $a !== (string) $b;
    }
}
