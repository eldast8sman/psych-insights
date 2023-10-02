<?php

namespace App\Models;

use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;

class Podcast extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'episode',
        'slug',
        'author',
        'categories',
        'summary',
        'release_date',
        'subscription_level',
        'cover_art',
        'podcast_link',
        'favourite_count',
        'opened_count',
        'status'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['title', 'episode'])
            ->saveSlugsTo('slug');
    }

    public function update_dependencies(){
        $podcasts = RecommendedPodcast::where('podcast_id', $this->id);
        if($podcasts->count() > 0){
            foreach($podcasts->get() as $podcast){
                $podcast->slug = $this->slug;
                $podcast->save();
            }
        }
    }
}
