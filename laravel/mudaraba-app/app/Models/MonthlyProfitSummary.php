<?php

namespace App\Models;

use App\Enums\MonthStatus;
use App\Services\AuditService;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'profit_month', 'transaction_date',
    'total_estimated_profit', 'total_actual_profit', 'total_advance_difference',
    'total_investor_advance_diff', 'total_investor_profit_due',
    'total_investor_retained',
    'my_profit', 'my_profit_ratio',
    'total_mudaraba_investment', 'active_investor_count',
    'status', 'finalized_by', 'finalized_at', 'locked_by', 'locked_at',
])]
class MonthlyProfitSummary extends Model
{
    use Auditable, HasFactory;

    protected $table = 'monthly_profit_summary';

    protected $primaryKey = 'profit_month';

    public $incrementing = false;

    protected $keyType = 'string';     // 'date' cast to string for PK

    public const UPDATED_AT = null;   // append-only snapshot per month (finalized_at/locked_at track lifecycle)

    protected $casts = [
        'transaction_date' => 'date',
        'total_estimated_profit' => 'decimal:2',
        'total_actual_profit' => 'decimal:2',
        'total_advance_difference' => 'decimal:2',
        'total_investor_advance_diff' => 'decimal:2',
        'total_investor_profit_due' => 'decimal:2',
        'total_investor_retained' => 'decimal:2',
        'my_profit' => 'decimal:2',
        'my_profit_ratio' => 'decimal:2',
        'total_mudaraba_investment' => 'decimal:2',
        'active_investor_count' => 'integer',
        'status' => MonthStatus::class,
        'finalized_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */
    public function scopeOpen($query)
    {
        return $query->where('status', MonthStatus::Open);
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', MonthStatus::Finalized);
    }

    public function scopeLocked($query)
    {
        return $query->where('status', MonthStatus::Locked);
    }

    /* -------------------------------------------------------
     * Helpers
     * ----------------------------------------------------- */

    /**
     * Audit hook override: log lock/unlock transitions as their own
     * actions (not generic 'update'). The MonthStatusController::lock
     * sets status to 'locked'; ::unlock sets it back to 'finalized'.
     *
     * Any other update (e.g. retained_earnings_service updating
     * total_investor_retained) falls through to the default 'update'
     * audit log.
     */
    protected static function shouldAudit(string $action, $model): bool
    {
        if ($action !== AuditService::ACTION_UPDATE) {
            return true;
        }

        // Status-driven audit: lock or unlock
        if ($model->wasChanged('status')) {
            $oldStatus = $model->getOriginal('status');
            $newStatus = $model->status;

            // Lock transition
            if ($newStatus === MonthStatus::Locked) {
                AuditService::log(
                    action: AuditService::ACTION_LOCK,
                    model: $model,
                    before: ['status' => $oldStatus instanceof MonthStatus ? $oldStatus->value : (string) $oldStatus],
                    after: ['status' => 'locked', 'locked_by' => $model->locked_by],
                );

                return false;
            }

            // Unlock transition (locked -> anything else)
            if ($oldStatus instanceof MonthStatus ? $oldStatus === MonthStatus::Locked : (string) $oldStatus === 'locked') {
                AuditService::log(
                    action: AuditService::ACTION_UNLOCK,
                    model: $model,
                    before: ['status' => 'locked'],
                    after: ['status' => $newStatus instanceof MonthStatus ? $newStatus->value : (string) $newStatus],
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Recompute M/Y profit ratio from total_actual_profit and my_profit.
     * Excel AG186 = my_profit / total_actual_profit × 100.
     */
    public function recomputeRatio(): float
    {
        $actual = (float) $this->total_actual_profit;

        return $actual > 0 ? round(((float) $this->my_profit / $actual) * 100, 2) : 0;
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isLocked(): bool
    {
        return $this->status === MonthStatus::Locked;
    }
}
