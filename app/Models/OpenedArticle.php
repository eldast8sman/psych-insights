<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenedArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'article_id',
        'frequency'
    ];
}
