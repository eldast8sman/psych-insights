<?php

use App\Models\DailyQuestion;
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
        Schema::create('daily_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DailyQuestion::class, 'daily_question_id');
            $table->string('option');
            $table->integer('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_question_options');
    }
};
