<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_sector_profit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectors')->cascadeOnDelete();
            // 1st of the month (e.g. 2026-07-01)
            $table->date('profit_month');
            $table->date('transaction_date')->nullable();

            // Excel Z column — estimated at start of month
            $table->decimal('estimated_profit', 15, 2)->default(0);
            // Excel X column — actual at end of month
            $table->decimal('actual_profit', 15, 2)->default(0);
            // Manual adjustment beyond estimated vs actual
            $table->decimal('profit_adjustment', 15, 2)->default(0);
            $table->boolean('is_estimate')->default(false);
            $table->enum('status', ['draft', 'finalized'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Excel-grade uniqueness: one profit row per sector per month
            $table->unique(['sector_id', 'profit_month']);
            $table->index(['profit_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_sector_profit');
    }
};
