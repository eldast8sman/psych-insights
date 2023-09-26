<?php

use App\Models\PaymentPlan;
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
        Schema::create('subscription_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->string('internal_ref');
            $table->foreignIdFor(PaymentPlan::class, 'payment_plan_id');
            $table->double('subscription_amount');
            $table->double('amount_paid');
            $table->double('promo_percentage')->default(0);
            $table->integer('promo_code_id')->nullable();
            $table->string('promo_code')->nullable();
            $table->double('promo_code_percentage')->default(0);
            $table->boolean('auto_renew')->default(1);
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payment_attempts');
    }
};
