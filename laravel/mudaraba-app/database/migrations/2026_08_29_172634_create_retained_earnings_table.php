<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retained_earnings', function (Blueprint $table) {
            // One row per month — PK is the month
            $table->date('profit_month')->primary();
            // BDT 200,000 target (configurable per month)
            $table->decimal('total_amount', 18, 2)->default(200000);
            // Split percentages — investors 71%, M/Y 29% (configurable)
            $table->decimal('investor_portion_pct', 5, 2)->default(71.00);
            $table->decimal('my_portion_pct', 5, 2)->default(29.00);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retained_earnings');
    }
};
