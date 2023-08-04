<?php

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
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('package')->unique();
            $table->integer('level');
            $table->text('description')->nullable();
            $table->string('podcast_limit')->default(0);
            $table->string('article_limit')->default(0);
            $table->string('audio_limit')->default(0);
            $table->string('video_limit')->default(0);
            $table->boolean('free_trial')->default(0);
            $table->double('first_time_promo')->nullable(0);
            $table->double('subsequent_promo')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
