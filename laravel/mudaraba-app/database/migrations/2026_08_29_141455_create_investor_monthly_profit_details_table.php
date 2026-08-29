<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_monthly_profit_details', function (Blueprint $table) {
            $table->id();
            $table->date('profit_month');
            $table->date('transaction_date')->nullable();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();

            // Phase 1-2: investment snapshot + ratio (Excel D + E)
            $table->decimal('investment', 20, 2)->default(0);
            $table->decimal('investment_ratio', 10, 6)->default(0);

            // Phase 2: Primary profit share — paid as advance (Excel Q/F)
            $table->decimal('primary_profit_share', 20, 2)->default(0);
            // Phase 3: Actual profit at full 100% share (Excel N)
            $table->decimal('actual_profit_at_full', 20, 2)->default(0);
            // Phase 3: Deed ratio as percentage 100/80/60 (Excel AF)
            $table->decimal('deed_ratio', 5, 2)->default(100.00);
            // Phase 3: Actual profit due after tier (Excel AG)
            $table->decimal('actual_profit_due', 20, 2)->default(0);
            // Phase 4: Advance difference / over-payment (Excel AH)
            // positive = investor was over-paid (owes M/Y), negative = under-paid
            $table->decimal('advance_difference', 20, 2)->default(0);
            // Phase 5: Retained earnings credit allocated to this investor (Excel AJ)
            $table->decimal('retained_earnings_credit', 20, 2)->default(0);
            // Phase 6: Net settlement (Excel AK) = advance_difference - retained_earnings_credit
            $table->decimal('net_settlement', 20, 2)->default(0);

            // Atomic batch UUID for the reconcile operation that produced this row
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Excel-grade uniqueness: one profit-detail row per investor per month
            $table->unique(['profit_month', 'investor_id']);
            $table->index(['profit_month', 'batch_uuid']);
            $table->index('investor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_monthly_profit_details');
    }
};
