<?php

use App\Models\Blog;
use App\Models\BlogCategory;
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
        Schema::create('blog_category_blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BlogCategory::class, 'blog_category_id');
            $table->foreignIdFor(Blog::class, 'blog_id');
            $table->dateTime('blog_created');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_category_blogs');
    }
};
