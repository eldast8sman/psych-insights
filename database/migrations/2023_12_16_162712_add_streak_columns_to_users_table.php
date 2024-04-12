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
            $table->date('last_login_date')->nullable()->after('deactivated');
            $table->integer('present_streak')->default(0)->after('last_login_date');
            $table->integer('longest_streak')->default(0)->after('present_streak');
            $table->integer('total_logins')->default(0)->after('longest_streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_date');
            $table->dropColumn('present_streak');
            $table->dropColumn('longest_streak');
            $table->dropColumn('total_logins');
        });
    }
};
