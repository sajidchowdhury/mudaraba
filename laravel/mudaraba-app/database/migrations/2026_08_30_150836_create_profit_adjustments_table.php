<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('advance_profit_adjustments');
        Schema::dropIfExists('advance_profit_adjustments_type_a');
        Schema::dropIfExists('advance_profit_adjustments_type_b');
        Schema::dropIfExists('profit_adjustments');

        Schema::create('profit_adjustments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['fund_a', 'fund_b', 'direct']);
            $table->enum('target_type', ['investor', 'sector']);
            $table->foreignId('investor_id')->nullable()->constrained('investors')->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            $table->date('transaction_date');
            $table->date('profit_month');
            $table->text('remarks')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

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
    }
};
