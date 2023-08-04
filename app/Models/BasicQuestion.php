<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'categories',
        'is_k10',
        'special_options'
    ];
}
