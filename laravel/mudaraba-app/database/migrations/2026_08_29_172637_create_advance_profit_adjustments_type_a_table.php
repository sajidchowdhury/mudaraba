<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_profit_adjustments_type_a', function (Blueprint $table) {
            $table->id();
            // One entry per date (unique) — Type A is a daily single-amount adjustment
            $table->date('transaction_date');
            $table->decimal('amount', 20, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_profit_adjustments_type_a');
    }
};
