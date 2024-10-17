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
        Schema::table('apple_pay_notifications', function (Blueprint $table) {
            $table->string('app_account_token')->nullable()->after('user_id');
            $table->string('notification_id')->nullable()->after('notification_data');
            $table->string('notification_type')->nullable()->after('notification_id');
            $table->string('transaction_id')->nullable()->after('notification_type');
            $table->string('original_transaction_id')->nullable()->after('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apple_pay_notifications', function (Blueprint $table) {
            //
        });
    }
};
