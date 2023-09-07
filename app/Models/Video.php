<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Video extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'subscription_level',
        'categories',
        'description',
        'duration',
        'photo',
        'video',
        'release_date',
        'status'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function update_dependencies(){
        $videos = RecommendedVideo::where('video_id', $this->id);
        if($videos->count() > 0){
            foreach($videos->get() as $video){
                $video->slug = $this->slug;
                $video->save();
            }
        }
    }
}
