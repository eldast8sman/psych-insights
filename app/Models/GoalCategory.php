<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class GoalCategory extends Model
{
    use HasFactory;

    use HasFactory, HasSlug;

    protected $fillable = [
        'category',
        'slug',
        'goal_setting_overview',
        'published'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('category')
            ->saveSlugsTo('slug');
    } 
}
