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
        Schema::create('basic_question_special_options', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BasicQuestion::class, 'basic_question_id');
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
        Schema::dropIfExists('basic_question_special_options');
    }
};
