<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_profit_due_ledger', function (Blueprint $table) {
            $table->foreignId('sector_id')->primary()->constrained('sectors')->cascadeOnDelete();
            // Cumulative sector profit adjustment (Excel Y2 equivalent per sector)
            $table->decimal('due', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_profit_due_ledger');
    }
};
