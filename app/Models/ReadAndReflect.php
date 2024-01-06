<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReadAndReflect extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'categories',
        'overview',
        'protocols',
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
        $reads = RecommendedReadAndReflect::where('read_and_reflect_id', $this->id);
        if($reads->count() > 0){
            foreach($reads->get() as $read){
                $read->slug = $this->slug;
                $read->save();
            }
        }
    }
}
