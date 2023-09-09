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
            $table->string('verification_token')->nullable()->after('token_expiry');
            $table->dateTime('verification_token_expiry')->nullable()->after('verification_token');
            $table->boolean('email_verified')->default(0)->after('verification_token_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verification_token');
            $table->dropColumn('verification_token_expiry');
            $table->dropColumn('email_verified');
        });
    }
};
