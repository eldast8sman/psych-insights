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
        Schema::create('distress_score_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('question_type');
            $table->integer('min');
            $table->integer('max');
            $table->string('verdict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distress_score_ranges');
    }
};
