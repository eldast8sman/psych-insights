<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Blog extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'categories',
        'title',
        'slug',
        'author',
        'duration',
        'body',
        'photo',
        'favourite_count',
        'status'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function update_dependencies(){
        $categs = BlogCategoryBlog::where('blog_id', $this->id);
        if($categs->count() > 0){
            foreach($categs->get() as $categ){
                $categ->blog_status = $this->status;
                $categ->save();
            }
        }
    }
}
