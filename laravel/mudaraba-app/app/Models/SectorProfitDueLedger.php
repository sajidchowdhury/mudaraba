<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sector_id', 'due'])]
class SectorProfitDueLedger extends Model
{
    protected $primaryKey = 'sector_id';

    public $incrementing = false;

    protected $table = 'sector_profit_due_ledger';

    protected $keyType = 'int';

    protected $casts = [
        'due' => 'decimal:2',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
