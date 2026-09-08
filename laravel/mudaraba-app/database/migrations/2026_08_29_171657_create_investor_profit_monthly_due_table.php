<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_profit_monthly_due', function (Blueprint $table) {
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->date('due_month');
            // Excel AH column equivalent — monthly advance difference for this investor
            $table->decimal('due', 20, 2)->default(0);

            $table->primary(['investor_id', 'due_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_profit_monthly_due');
    }
};
