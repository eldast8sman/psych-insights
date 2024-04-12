<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicQuestionSpecialOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'basic_question_id',
        'option',
        'value'
    ];
}
