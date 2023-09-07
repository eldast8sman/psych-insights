<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenedPodcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'podcast_id',
        'frequency'
    ];
}
