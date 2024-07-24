<?php

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
        Schema::create('apple_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->string('app_account_token');
            $table->string('original_transaction_id');
            $table->integer('subscription_package_id');
            $table->integer('payment_plan_id');
            $table->integer('transaction_id')->nullable();
            $table->boolean('value_given')->default(0);
            $table->text('transaction_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apple_transaction_details');
    }
};
