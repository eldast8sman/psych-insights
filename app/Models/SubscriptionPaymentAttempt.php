<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'internal_ref',
        'payment_plan_id',
        'subscription_amount',
        'amount_paid',
        'promo_percentage',
        'promo_code_id',
        'promo_code',
        'promo_code_percentage',
        'auto_renew',
        'status'
    ];
}
