<?php

use App\Models\User;
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
        Schema::create('question_answer_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->string('question_type');
            $table->text('answers');
            $table->integer('k10_scores')->default(0);
            $table->integer('total_score')->default(0);
            $table->string('distress_level')->nullable();
            $table->text('premium_scores');
            $table->text('category_scores');
            $table->string('highest_category_id');
            $table->string('highest_category');
            $table->date('next_question');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_answer_summaries');
    }
};
