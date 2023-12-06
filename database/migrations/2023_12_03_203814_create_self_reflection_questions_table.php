<?php

use App\Models\SelfReflectionCategory;
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
        Schema::create('self_reflection_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SelfReflectionCategory::class, 'category_id');
            $table->text('question');
            $table->string('question_type')->default('open_ended');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('self_reflection_questions');
    }
};
