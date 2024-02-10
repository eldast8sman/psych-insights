<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalPlanQuestions extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_category_id',
        'title',
        'pre_text',
        'example',
        'weekly_plan'
    ];
}
