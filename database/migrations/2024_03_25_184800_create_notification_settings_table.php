<?php

use App\Models\Admin\Admin;
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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Admin::class, 'admin_id');
            $table->boolean('new_user_notification')->default(true);
            $table->boolean('new_subscriber_notification')->default(true);
            $table->boolean('subscription_renewal_notification')->default(true);
            $table->boolean('account_deactivation_notification')->default(true);
            $table->boolean('prolong_inactivity_notification')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
