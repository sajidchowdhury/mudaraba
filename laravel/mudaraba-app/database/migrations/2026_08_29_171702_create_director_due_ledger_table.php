<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('director_due_ledger', function (Blueprint $table) {
            $table->foreignId('director_id')->primary()->constrained('directors')->cascadeOnDelete();
            // Cumulative M/Y payable (positive = pool owes M/Y; M/Y withdraw reduces this)
            $table->decimal('due', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('director_due_ledger');
    }
};
