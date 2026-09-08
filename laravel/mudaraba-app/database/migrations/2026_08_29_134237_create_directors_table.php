<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_my')->default(false);        // flag the primary M/Y
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_my', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directors');
    }
};
