<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // employee_id is added in a later migration (after employees exists) to avoid circular FK
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('username', 50)->unique();
            $table->string('email', 120)->nullable()->unique();
            $table->string('password_hash');
            $table->enum('role', ['user', 'admin', 'superadmin'])->default('user');
            $table->string('status', 10)->default('Active');
            $table->time('login_start')->nullable();
            $table->time('login_end')->nullable();
            $table->string('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deleted_at']);
            $table->index('role');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
