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
        'product_id',
        'user_id'
    ];
}
