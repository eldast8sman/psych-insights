<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendedListenAndLearn extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'listen_and_learn_id',
        'slug',
        'opened'
    ];
}
