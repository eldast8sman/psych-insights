<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stripe_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('internal_ref');
            $table->foreignIdFor(User::class, 'user_id');
            $table->string('intent_id');
            $table->string('client_secret')->nullable();
            $table->double('amount');
            $table->text('intent_data');
            $table->string('purpose');
            $table->integer('purpose_id')->nullable();
            $table->boolean('auto_renew');
            $table->boolean('value_given');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_payment_intents');
    }
};
