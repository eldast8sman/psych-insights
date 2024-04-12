<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendedReadAndReflect extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'read_and_reflect_id',
        'slug',
        'opened'
    ];
}
