<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('reference', 120)->nullable();    // "MD", "German", etc.
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            // Deed ratio: 100 (full share), 80 (reduced), 60 (lowest) — stored as enum
            // for portable CHECK enforcement across SQLite/MySQL/Postgres.
            $table->enum('deed_ratio', ['60', '80', '100'])->default('100');
            $table->date('start_profit_month')->nullable();  // when they begin earning
            $table->date('end_profit_month')->nullable();    // when they stop
            $table->enum('status', ['active', 'inactive', 'closed'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deleted_at']);
            $table->index('deed_ratio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
