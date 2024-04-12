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
        Schema::table('learn_and_dos', function (Blueprint $table) {
            $table->boolean('published')->default(1)->after('status');
        });
        Schema::table('read_and_reflects', function (Blueprint $table) {
            $table->boolean('published')->default(1)->after('status');
        });
        Schema::table('listen_and_learns', function (Blueprint $table) {
            $table->boolean('published')->default(1)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learn_and_dos', function (Blueprint $table) {
            $table->dropColumn('published');
        });
        Schema::table('read_and_reflects', function (Blueprint $table) {
            $table->dropColumn('published');
        });
        Schema::table('listen_and_learns', function (Blueprint $table) {
            $table->dropColumn('published');
        });
    }
};
