<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'option',
        'value'
    ];
}
