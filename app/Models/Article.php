<?php

namespace App\Models;

use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;

class Article extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'categories',
        'subscription_level',
        'author',
        'duration',
        'publication_date',
        'content',
        'photo',
        'status'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function update_dependencies(){
        $articles = RecommendedArticle::where('article_id', $this->id);
        if($articles->count() > 0){
            foreach($articles->get() as $article){
                $article->slug = $this->slug;
                $article->save();
            }
        }
    }
}
