<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistressScoreRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_type',
        'min',
        'max',
        'verdict',
        'welcome_message'
    ];

    protected $hidden = [
        'question_type'
    ];
}
