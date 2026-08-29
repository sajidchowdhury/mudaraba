<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'mobile', 'address', 'status'])]
class Sector extends Model
{
    use HasFactory, SoftDeletes;

    public function sectorInvestments(): HasMany
    {
        return $this->hasMany(SectorInvestment::class)->orderBy('transaction_date');
    }

    public function monthlySectorProfits(): HasMany
    {
        return $this->hasMany(MonthlySectorProfit::class)->orderBy('profit_month', 'desc');
    }
}
