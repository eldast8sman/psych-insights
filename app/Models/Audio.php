<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Audio extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'categories',
        'tag',
        'description',
        'subscription_level',
        'release_date',
        'audio',
        'status'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function update_dependencies(){
        $audios = RecommendedAudio::where('audio_id', $this->id);
        if($audios->count() > 0){
            foreach($audios->get() as $audio){
                $audio->slug = $this->slug;
                $audio->save();
            }
        }
    }
}
