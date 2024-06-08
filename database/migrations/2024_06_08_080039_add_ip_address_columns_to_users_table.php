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
        Schema::table('users', function (Blueprint $table) {
            $table->string('signup_ip')->after('signup_country')->nullable();
            $table->string('signup_timezone')->after('signup_ip')->nullable();
            $table->string('last_ip')->after('last_country')->nullable();
            $table->string('last_timezone')->after('last_ip')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signup_ip');
            $table->dropColumn('signup_timezone');
            $table->dropColumn('last_ip');
            $table->dropColumn('last_timezone');
        });
    }
};
