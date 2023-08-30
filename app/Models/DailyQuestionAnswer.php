<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyQuestionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'answer_date',
        'answers',
        'category_scores',
        'computed'
    ];

    const UPDATED_AT = NULL;
}
