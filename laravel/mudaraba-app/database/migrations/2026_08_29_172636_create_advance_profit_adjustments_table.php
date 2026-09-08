<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_profit_adjustments', function (Blueprint $table) {
            $table->id();
            // Type C can target EITHER a sector OR an investor (one is set, other is null)
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

            $table->index(['sector_id', 'profit_month']);
            $table->index(['investor_id', 'profit_month']);
            $table->index('batch_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_profit_adjustments');
    }
};
