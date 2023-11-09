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
        Schema::create('learn_and_dos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('categories');
            $table->string('slug');
            $table->integer('photo')->nullable();
            $table->text('overview');
            $table->integer('subscription_level')->default(0);
            $table->longText('post_text')->nullable();
            $table->string('activity_title')->default('Activities');
            $table->longText('activity_overview')->nullable();
            $table->integer('favourite_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learn_and_dos');
    }
};
