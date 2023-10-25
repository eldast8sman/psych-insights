<?php

use App\Models\ListenAndLearn;
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
        Schema::create('listen_and_learn_audio', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ListenAndLearn::class, 'listen_and_learn_id');
            $table->integer('audio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listen_and_learn_audio');
    }
};
