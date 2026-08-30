<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'mobile', 'address', 'is_my'])]
class Director extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'is_my' => 'boolean',
    ];

    public function directorTransactions(): HasMany
    {
        return $this->hasMany(DirectorTransaction::class)->orderBy('transaction_date');
    }

    public function dueLedger(): HasOne
    {
        return $this->hasOne(DirectorDueLedger::class);
    }
}
