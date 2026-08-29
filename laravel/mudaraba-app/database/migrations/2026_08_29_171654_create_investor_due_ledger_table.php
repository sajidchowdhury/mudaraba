<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_due_ledger', function (Blueprint $table) {
            // PK is the investor_id — one running-balance row per investor
            $table->foreignId('investor_id')->primary()->constrained('investors')->cascadeOnDelete();
            // Cumulative due balance (positive = investor owes capital, negative = credit)
            $table->decimal('due', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_due_ledger');
    }
};
