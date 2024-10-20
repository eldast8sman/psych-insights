<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'journal',
        'color',
        'pinned',
        'created_time',
        'updated_time'
    ];
}
