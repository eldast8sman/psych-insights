<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrerequisiteQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'basic_question_id',
        'prerequisite_id',
        'prerequisite_value',
        'action',
        'default_value'
    ];
}
