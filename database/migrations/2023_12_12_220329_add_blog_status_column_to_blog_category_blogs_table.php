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
        Schema::table('blog_category_blogs', function (Blueprint $table) {
            $table->boolean('blog_status')->default(1)->after('blog_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_category_blogs', function (Blueprint $table) {
            $table->dropColumn('blog_status');
        });
    }
};
