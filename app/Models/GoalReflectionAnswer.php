<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalReflectionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'goal_category_id',
        'answers'
    ];
}
