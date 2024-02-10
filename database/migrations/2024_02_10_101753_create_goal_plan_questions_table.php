<?php

use App\Models\GoalCategory;
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
        Schema::create('goal_plan_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(GoalCategory::class, 'goal_category_id');
            $table->string('title');
            $table->text('pre_text')->nullable();
            $table->text('example')->nullable();
            $table->boolean('weekly_plan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_plan_questions');
    }
};
