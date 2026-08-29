<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('director_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('director_id')->constrained('directors')->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            // 'withdraw' = M/Y takes money out of the pool; 'return' = puts it back
            $table->enum('type', ['withdraw', 'return']);
            $table->date('transaction_month');
            $table->date('transaction_date');
            $table->text('remarks')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['director_id', 'transaction_month']);
            $table->index('batch_uuid');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('director_transactions');
    }
};
