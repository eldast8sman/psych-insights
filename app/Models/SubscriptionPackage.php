<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionPackage extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'package',
        'slug',
        'level',
        'description',
        'podcast_limit',
        'article_limit',
        'audio_limit',
        'video_limit',
        'free_trial',
        'first_time_promo',
        'subsequent_promo',
        'free_package'
    ];

    protected $hidden = [
        'free_trial',
        'free_package'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('package')
            ->saveSlugsTo('slug');
    }
}
