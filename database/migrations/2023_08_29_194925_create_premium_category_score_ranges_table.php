<?php

use App\Models\Category;
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
        Schema::create('premium_category_score_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Category::class, 'category_id');
            $table->integer('min');
            $table->integer('max');
            $table->string('verdict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premium_category_score_ranges');
    }
};
