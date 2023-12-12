<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogCategoryBlog extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_category_id',
        'blog_id',
        'blog_created',
        'blog_status'
    ];
}
