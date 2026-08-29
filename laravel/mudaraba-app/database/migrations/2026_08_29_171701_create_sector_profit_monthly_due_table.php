<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_profit_monthly_due', function (Blueprint $table) {
            $table->foreignId('sector_id')->constrained('sectors')->cascadeOnDelete();
            $table->date('due_month');
            // Per-sector monthly profit adjustment (Excel Y column)
            $table->decimal('due', 20, 2)->default(0);

            $table->primary(['sector_id', 'due_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_profit_monthly_due');
    }
};
