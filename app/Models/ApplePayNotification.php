<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplePayNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'notification_data',
        'notification_type',
        'notification_id',
        'transaction_id',
        'original_transaction_id',
        'product_id',
        'user_id',
        'app_account_token',
        'value_given'
    ];
}
