<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_question_id',
        'option',
        'value'
    ];
}
