<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendedAudio extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'audio_id',
        'slug',
        'opened'
    ];
}
