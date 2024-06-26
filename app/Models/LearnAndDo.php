<?php

namespace App\Models;

use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;

class LearnAndDo extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'categories',
        'photo',
        'overview',
        'subscription_level',
        'post_text',
        'activity_title',
        'activity_overview',
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
        $reads = RecommendedLearnAndDo::where('learn_and_do_id', $this->id);
        if($reads->count() > 0){
            foreach($reads->get() as $read){
                $read->slug = $this->slug;
                $read->save();
            }
        }
    }
}
