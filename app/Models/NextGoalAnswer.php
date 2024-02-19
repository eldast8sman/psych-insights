<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NextGoalAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'goal_category_id',
        'reflection_answered',
        'goal_set',
        'next_date'
    ];
}
