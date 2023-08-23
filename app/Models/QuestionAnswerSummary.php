<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAnswerSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_type',
        'answers',
        'total_score',
        'premium_scores',
        'highest_category',
        'next_question'
    ];

    const UPDATED_AT = NULL;
}
