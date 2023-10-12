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
        Schema::table('question_answer_summaries', function (Blueprint $table) {
            $table->integer('second_highest_category_id')->nullable()->after('highest_category');
            $table->string('second_highest_category')->nullable()->after('second_highest_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_answer_summaries', function (Blueprint $table) {
            $table->dropColumn('second_highest_category_id');
            $table->dropColumn('second_highest_category');
        });
    }
};
