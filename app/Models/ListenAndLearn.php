<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListenAndLearn extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'categories',
        'overview',
        'audio_overview',
        'photo',
        'subscription_level',
        'favourite_count',
        'opened_count',
        'status',
        'published'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function update_dependencies(){
        $learns = RecommendedListenAndLearn::where('listen_and_learn_id', $this->id);
        if($learns->count() > 0){
            foreach($learns->get() as $learn){
                $learn->slug = $this->slug;
                $learn->save();
            }
        }
    }
}
