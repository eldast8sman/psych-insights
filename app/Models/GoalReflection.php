<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalReflection extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_category_id',
        'type',
        'title',
        'pre_text',
        'post_text',
        'min',
        'max'
    ];
}
