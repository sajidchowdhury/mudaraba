<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('director_monthly_due', function (Blueprint $table) {
            $table->foreignId('director_id')->constrained('directors')->cascadeOnDelete();
            $table->date('due_month');
            // Excel AG184 (M/Y profit) + DirectorTransaction amounts for this month
            $table->decimal('due', 20, 2)->default(0);

            $table->primary(['director_id', 'due_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('director_monthly_due');
    }
};
