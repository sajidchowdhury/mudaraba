<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sector_id', 'investor_id', 'amount',
    'transaction_date', 'profit_month', 'remarks',
    'batch_uuid', 'created_by',
])]
class AdvanceProfitAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'batch_uuid' => 'string',
    ];

    /* -------------------------------------------------------
     * Polymorphic relationships (Type C targets sector OR investor)
     * ----------------------------------------------------- */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* -------------------------------------------------------
     * Helpers
     * ----------------------------------------------------- */

    /**
     * Which entity type does this adjustment target?
     */
    public function targetType(): ?string
    {
        if ($this->sector_id !== null) {
            return 'sector';
        }
        if ($this->investor_id !== null) {
            return 'investor';
        }

        return null;
    }

    /**
     * Resolve the related entity model (sector or investor).
     */
    public function target(): ?Model
    {
        return $this->sector ?? $this->investor;
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

    public function scopeForInvestor($query, int $investorId)
    {
        return $query->where('investor_id', $investorId);
    }

    public function scopeInBatch($query, string $batchUuid)
    {
        return $query->where('batch_uuid', $batchUuid);
    }
}
