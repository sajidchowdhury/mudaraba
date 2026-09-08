<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectors')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['add', 'withdraw']);
            $table->date('transaction_date');
            $table->text('remarks')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sector_id', 'transaction_date']);
            $table->index('batch_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_investments');
    }
};
