<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'categories'
    ];

    public function options(){
        return DailyQuestionOption::where('daily_question_id', $this->id)->orderBy('value', 'desc');
    }
}
