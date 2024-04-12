<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearnAndDoAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'learn_and_do_id',
        'answers'
    ];
}
