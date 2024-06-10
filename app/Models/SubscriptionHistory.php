<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_package_id',
        'payment_plan_id',
        'subscription_amount',
        'amount_paid',
        'promo_percentage',
        'promo_code_id',
        'promo_code',
        'promo_code_percentage',
        'start_date',
        'end_date',
        'grace_end',
        'auto_renew',
        'status'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
