<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retained_earnings_distributions', function (Blueprint $table) {
            $table->id();
            $table->date('profit_month');
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            // Snapshot of the investor's ratio at the time of distribution
            $table->decimal('investment_ratio', 10, 6)->default(0);
            // Amount allocated = investor_portion × ratio (Excel AJ column per investor)
            $table->decimal('amount', 18, 2)->default(0);
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();

            // One distribution per investor per month
            $table->unique(['profit_month', 'investor_id']);
            $table->index(['profit_month', 'batch_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retained_earnings_distributions');
    }
};
