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
        'k10_scores',
        'total_score',
        'distress_level',
        'premium_scores',
        'category_scores',
        'highest_category_id',
        'highest_category',
        'second_highest_category_id',
        'second_highest_category',
        'next_question'
    ];

    const UPDATED_AT = NULL;
}
