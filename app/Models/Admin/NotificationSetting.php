<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'new_user_notification',
        'new_subscriber_notification',
        'subscription_renewal_notification',
        'account_deactivation_notification',
        'prolong_inactivity_notification'
    ];
}
