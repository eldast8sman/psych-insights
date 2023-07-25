<?php

use App\Models\BasicQuestion;
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
        Schema::create('prerequisite_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BasicQuestion::class, 'basic_question_id');
            $table->foreignIdFor(BasicQuestion::class, 'prerequisite_id');
            $table->integer('prerequisite_value')->default(1);
            $table->string('action')->default('skip');
            $table->integer('default_value')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prerequisite_questions');
    }
};
