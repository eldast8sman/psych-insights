<?php

use App\Models\LearnAndDo;
use App\Models\LearnAndDoActivity;
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
        Schema::create('learn_and_do_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(LearnAndDo::class, 'learn_and_do_id');
            $table->foreignIdFor(LearnAndDoActivity::class, 'activity_id');
            $table->text('question');
            $table->string('answer_type')->default('text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learn_and_do_questions');
    }
};
