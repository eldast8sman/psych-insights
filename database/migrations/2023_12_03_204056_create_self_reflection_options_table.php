<?php

use App\Models\SelfReflectionQuestion;
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
        Schema::create('self_reflection_options', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SelfReflectionQuestion::class, 'question_id');
            $table->text('option');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('self_reflection_options');
    }
};
