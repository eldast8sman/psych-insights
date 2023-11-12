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
        Schema::table('listen_and_learns', function (Blueprint $table) {
            $table->integer('favourite_count')->default(0)->after('subscription_level');
            $table->integer('opened_count')->default(0)->after('favourite_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listen_and_learns', function (Blueprint $table) {
            $table->dropColumn('favourite_count');
            $table->dropColumn('opened_count');
        });
    }
};
