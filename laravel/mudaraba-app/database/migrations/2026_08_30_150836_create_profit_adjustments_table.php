<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the 3 separate tables from Phase 1.5 (replaced by unified table)
        Schema::dropIfExists('advance_profit_adjustments');
        Schema::dropIfExists('advance_profit_adjustments_type_a');
        Schema::dropIfExists('advance_profit_adjustments_type_b');

        Schema::create('profit_adjustments', function (Blueprint $table) {
            $table->id();
            // Fund type: 'fund_a' (was Type A), 'fund_b' (was Type B), 'direct' (was Type C)
            $table->enum('type', ['fund_a', 'fund_b', 'direct']);
            // Target: who is being adjusted
            $table->enum('target_type', ['investor', 'sector']);
            // Polymorphic: one of these is set, the other is null
            $table->foreignId('investor_id')->nullable()->constrained('investors')->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->cascadeOnDelete();
            // Always positive; the ledger decrease is implicit in the business logic
            $table->decimal('amount', 20, 2);
            $table->date('transaction_date');
            $table->date('profit_month');
            $table->text('remarks')->nullable();
            // Groups a batch of adjustments (e.g., all investors + sectors in one Fund A entry)
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fund balance computation + reporting
            $table->index(['type', 'target_type']);
            $table->index(['investor_id', 'profit_month']);
            $table->index(['sector_id', 'profit_month']);
            $table->index('batch_uuid');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_adjustments');

        // Recreate the old tables (for rollback)
        Schema::create('advance_profit_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->cascadeOnDelete();
            $table->foreignId('investor_id')->nullable()->constrained('investors')->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            $table->date('transaction_date');
            $table->date('profit_month');
            $table->text('remarks')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('advance_profit_adjustments_type_a', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->decimal('amount', 20, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('transaction_date');
        });

        Schema::create('advance_profit_adjustments_type_b', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->decimal('amount', 20, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('transaction_date');
        });
    }
};
