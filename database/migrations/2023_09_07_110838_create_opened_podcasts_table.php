<?php

use App\Models\Podcast;
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
        Schema::create('opened_podcasts', function (Blueprint $table) {
            $table->id();                                                                                           
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(Podcast::class, 'podcast_id');
            $table->integer('frequency')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opened_podcasts');
    }
};
