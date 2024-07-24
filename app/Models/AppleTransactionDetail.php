<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppleTransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'app_account_token',
        'originsl_transaction_id',
        'subscription_package_id',
        'payment_plan_id',
        'transaction_id',
        'value_given',
        'transaction_details'
    ];
}
