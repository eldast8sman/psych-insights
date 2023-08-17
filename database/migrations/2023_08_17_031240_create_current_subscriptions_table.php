<?php

use App\Models\PaymentPlan;
use App\Models\SubscriptionPackage;
use App\Models\User;
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
        Schema::create('current_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(SubscriptionPackage::class, 'subscription_package_id');
            $table->foreignIdFor(PaymentPlan::class, 'payment_plan_id');
            $table->double('amount_paid')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_subscriptions');
    }
};
