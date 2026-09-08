<?php

namespace App\Models;

use App\Enums\DirectorTransactionType;
use App\Traits\DueManager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'director_id', 'amount', 'type', 'transaction_month', 'transaction_date',
    'remarks', 'batch_uuid', 'created_by',
])]
class DirectorTransaction extends Model
{
    use DueManager, HasFactory, SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => DirectorTransactionType::class,
        'transaction_month' => 'date',
        'transaction_date' => 'date',
        'batch_uuid' => 'string',
    ];

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForDirector($query, int $directorId)
    {
        return $query->where('director_id', $directorId);
    }

    public function scopeTillMonth($query, string $month)
    {
        return $query->where('transaction_month', '<=', $month);
    }

    public function scopeInBatch($query, string $batchUuid)
    {
        return $query->where('batch_uuid', $batchUuid);
    }

    protected function dueLedgerConfig(): array
    {
        return [
            'entity_column' => 'director_id',
            'cumulative_model' => DirectorDueLedger::class,
            'monthly_model' => DirectorMonthlyDue::class,
        ];
    }

    /**
     * Signed effect on the director's (M/Y) payable balance.
     * Withdraw = -, Return = +.
     */
    public function signedAmount(): float
    {
        return (float) $this->amount * $this->type->sign();
    }
}
