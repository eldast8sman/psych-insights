<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearnAndDoActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'learn_and_do_id',
        'title',
        'overview',
        'example',
        'post_text'
    ];
}
