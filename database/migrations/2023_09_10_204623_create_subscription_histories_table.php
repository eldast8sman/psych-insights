<?php

use App\Models\PaymentPlan;
use App\Models\PromoCode;
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
        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(SubscriptionPackage::class, 'subscription_package_id');
            $table->foreignIdFor(PaymentPlan::class, 'payment_plan_id');
            $table->double('subscription_amount')->default(0);
            $table->double('amount_paid')->default(0);
            $table->double('promo_percentage')->default(0);
            $table->foreignIdFor(PromoCode::class, 'promo_code_id')->nullable();
            $table->string('promo_code')->nullable();
            $table->double('promo_code_percentage')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_histories');
    }
};
