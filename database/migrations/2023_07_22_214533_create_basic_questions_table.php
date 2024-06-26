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
        Schema::create('basic_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('categories');
            $table->boolean('is_k10')->default(1);
            $table->boolean('special_options')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basic_questions');
    }
};
