<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_profit_summary', function (Blueprint $table) {
            // Primary key IS the month (1st of month) — one summary row per month
            $table->date('profit_month')->primary();
            $table->date('transaction_date')->nullable();

            // Excel Z2 — total estimated/primary shared profit
            $table->decimal('total_estimated_profit', 18, 2)->default(0);
            // Excel X2 — total actual profit realized
            $table->decimal('total_actual_profit', 18, 2)->default(0);
            // Excel Y2 — sector advance difference (Z2 - X2)
            $table->decimal('total_advance_difference', 18, 2)->default(0);
            // Excel AH182 — total investor advance difference (over-payment)
            $table->decimal('total_investor_advance_diff', 18, 2)->default(0);
            // Excel AG182 — total investor actual profit due
            $table->decimal('total_investor_profit_due', 18, 2)->default(0);
            // Excel AJ182 — total investor retained earnings credit
            $table->decimal('total_investor_retained', 18, 2)->default(0);
            // Excel AG184 — M/Y profit (= total_actual - total_investor_profit_due)
            $table->decimal('my_profit', 18, 2)->default(0);
            // Excel AG186 — M/Y profit ratio % (target ~29.13%)
            $table->decimal('my_profit_ratio', 6, 2)->default(0);
            // Excel D181 — total Mudaraba investment snapshot
            $table->decimal('total_mudaraba_investment', 20, 2)->default(0);
            $table->integer('active_investor_count')->default(0);

            $table->enum('status', ['open', 'finalized', 'locked'])->default('open');
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_profit_summary');
    }
};
