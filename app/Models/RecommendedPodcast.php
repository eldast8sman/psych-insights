<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendedPodcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'podcast_id',
        'slug',
        'opened'
    ];
}
