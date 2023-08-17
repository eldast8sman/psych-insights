<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 500);
            $table->string('email', 500);
            $table->string('password')->nullable();
            $table->string('token')->nullable();
            $table->dateTime('token_expiry')->nullable();
            $table->integer('profile_photo')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->dateTime('prev_login')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
