<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_profit_due_ledger', function (Blueprint $table) {
            $table->foreignId('investor_id')->primary()->constrained('investors')->cascadeOnDelete();
            // Cumulative profit due (positive = investor was over-paid in advance, owes M/Y back)
            $table->decimal('due', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_profit_due_ledger');
    }
};
