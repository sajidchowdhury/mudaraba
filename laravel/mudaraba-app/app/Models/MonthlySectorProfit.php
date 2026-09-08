<?php

namespace App\Models;

use App\Enums\SectorProfitStatus;
use App\Services\AuditService;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sector_id', 'profit_month', 'transaction_date',
    'estimated_profit', 'actual_profit', 'profit_adjustment',
    'is_estimate', 'status', 'created_by', 'finalized_by', 'finalized_at',
])]
class MonthlySectorProfit extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'monthly_sector_profit';

    protected $casts = [
        'transaction_date' => 'date',
        'estimated_profit' => 'decimal:2',
        'actual_profit' => 'decimal:2',
        'profit_adjustment' => 'decimal:2',
        'is_estimate' => 'boolean',
        'status' => SectorProfitStatus::class,
        'finalized_at' => 'datetime',
    ];

    /* -------------------------------------------------------
     * Relationships
     * ----------------------------------------------------- */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('profit_month', $month);
    }

    public function scopeForSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', SectorProfitStatus::Finalized);
    }

    /* -------------------------------------------------------
     * Helpers
     * ----------------------------------------------------- */

    /**
     * Audit hook override: when status transitions to 'finalized',
     * log the action as 'finalize' (not 'update'). Otherwise use the
     * standard create/update/delete audit.
     *
     * This is called by the Auditable trait's `updated` event listener
     * BEFORE the standard `AuditService::log('update', ...)` fires.
     * If we log here, we return false from shouldAudit('update') to
     * suppress the duplicate update log.
     */
    protected static function shouldAudit(string $action, $model): bool
    {
        // If this is an UPDATE and the status column is changing to 'finalized',
        // log a 'finalize' event here and skip the generic 'update' log.
        if ($action === AuditService::ACTION_UPDATE
            && $model->wasChanged('status')
            && $model->status === SectorProfitStatus::Finalized
        ) {
            $original = (new static())->setRawAttributes($model->getOriginal());
            AuditService::log(
                action: AuditService::ACTION_FINALIZE,
                model: $model,
                before: ['status' => $original->status?->value ?? 'draft'],
                after: ['status' => 'finalized'],
            );

            return false; // suppress the generic 'update' log
        }

        return true;
    }

    /**
     * Excel Y column — sector advance difference (estimated - actual).
     * Positive = investors were over-paid in advance, must return the diff.
     */
    public function advanceDifference(): float
    {
        return (float) $this->estimated_profit - (float) $this->actual_profit;
    }

    /**
     * Convenience: has actual profit been entered yet?
     */
    public function hasActuals(): bool
    {
        return $this->status === SectorProfitStatus::Finalized
            || (float) $this->actual_profit > 0;
    }
}
