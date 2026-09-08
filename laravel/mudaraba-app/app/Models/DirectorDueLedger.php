<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['director_id', 'due'])]
class DirectorDueLedger extends Model
{
    protected $primaryKey = 'director_id';

    public $incrementing = false;

    protected $table = 'director_due_ledger';

    protected $keyType = 'int';

    protected $casts = [
        'due' => 'decimal:2',
    ];

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class);
    }
}
