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
        Schema::table('learn_and_do_questions', function (Blueprint $table) {
            $table->integer('number_of_list')->nullable()->after('answer_type');
            $table->integer('minimum')->nullable()->after('number_of_list');
            $table->integer('maximum')->nullable()->after('minimum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learn_and_do_questions', function (Blueprint $table) {
            $table->dropColumn('number_of_list');
            $table->dropColumn('minimum');
            $table->dropColumn('maximum');
        });
    }
};
