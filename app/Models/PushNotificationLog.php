<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'last_notification',
        'next_notification'
    ];
}
