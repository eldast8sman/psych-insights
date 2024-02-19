<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGoalReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'goal_category_id',
        'reminder',
        'reminder_day',
        'reminder_time',
        'next_reminder',
        'reminder_type',
        'status'
    ];
}
