<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelfReflectionQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question',
        'question_type'
    ];
}
