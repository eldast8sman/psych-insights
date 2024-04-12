<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearnAndDoQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'learn_and_do_id',
        'activity_id',
        'question',
        'answer_type',
        'number_of_list',
        'minimum',
        'maximum'
    ];
}
