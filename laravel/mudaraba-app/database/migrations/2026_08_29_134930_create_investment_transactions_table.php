<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            // Always > 0; type column controls the direction (add vs withdraw)
            $table->decimal('amount', 20, 2);
            // 'add' increases the investor's capital balance; 'withdraw' decreases it
            $table->enum('type', ['add', 'withdraw']);
            // Month this transaction applies to (1st of month, used for "investment till month")
            $table->date('transaction_month');
            // Actual date of payment (may differ from posting month)
            $table->date('transaction_date');
            $table->text('remarks')->nullable();
            // Groups atomic month-reconcile operations so a failed batch can be rolled back
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Hot path: investor balance computation by month
            $table->index(['investor_id', 'transaction_month']);
            $table->index('batch_uuid');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};
