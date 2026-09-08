<?php

namespace App\Models;

use App\Enums\InvestmentType;
use App\Traits\DueManager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sector_id', 'amount', 'type', 'transaction_date',
    'remarks', 'batch_uuid', 'created_by',
])]
class SectorInvestment extends Model
{
    use DueManager, HasFactory, SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => InvestmentType::class,
        'transaction_date' => 'date',
        'batch_uuid' => 'string',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    public function scopeInBatch($query, string $batchUuid)
    {
        return $query->where('batch_uuid', $batchUuid);
    }

    protected function dueLedgerConfig(): array
    {
        return [
            'entity_column' => 'sector_id',
            'cumulative_model' => SectorDueLedger::class,
            'monthly_model' => SectorMonthlyDue::class,
        ];
    }

    /**
     * Signed effect on sector balance (Add = +, Withdraw = -).
     */
    public function signedAmount(): float
    {
        return (float) $this->amount * $this->type->sign();
    }
}
