<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Book extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'categories',
        'slug',
        'author',
        'summary',
        'price',
        'publication_year',
        'subscription_level',
        'book_cover',
        'purchase_link',
        'favourite_count',
        'opened_count',
        'status'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function update_dependencies(){
        $books = RecommendedBook::where('book_id', $this->id);
        if($books->count() > 0){
            foreach($books->get() as $book){
                $book->slug = $this->slug;
                $book->save();
            }
        }
    }
}
