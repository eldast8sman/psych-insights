<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelfReflectionCategory extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'category',
        'slug',
        'details'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('category')
            ->saveSlugsTo('slug');
    }
}
