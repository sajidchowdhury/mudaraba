<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['investor_id', 'due'])]
class InvestorDueLedger extends Model
{
    protected $primaryKey = 'investor_id';

    public $incrementing = false;

    protected $table = 'investor_due_ledger';

    protected $keyType = 'int';

    protected $casts = [
        'due' => 'decimal:2',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
