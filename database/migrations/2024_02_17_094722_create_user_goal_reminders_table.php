<?php

use App\Models\GoalCategory;
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
        Schema::create('user_goal_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(GoalCategory::class, 'goal_category_id');
            $table->text('reminder');
            $table->string('reminder_day');
            $table->time('reminder_time');
            $table->dateTime('next_reminder');
            $table->string('reminder_type')->default('recurring');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_goal_reminders');
    }
};
