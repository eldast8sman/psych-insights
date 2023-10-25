<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListenAndLearnAudio extends Model
{
    use HasFactory;

    protected $fillable = [
        'listen_and_learn_id',
        'audio'
    ];
}
