<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\OpenedBook;
use Illuminate\Http\Request;
use App\Models\OpenedResources;
use App\Models\RecommendedBook;

class BookController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api', ['except' => ['recommend_books']]);
        $this->user = AuthController::user();
    }

    public static function recommend_books($limit, $user_id, $cat_id, $level=0){
        $rec_books = RecommendedBook::where('user_id', $user_id);
        if($rec_books->count() > 0){
            foreach($rec_books->get() as $rec_book){
                $rec_book->delete();
            }
        }

        if($limit > 0){
            $opened_books = [];
            $op_books = OpenedBook::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_books->count() > 0){
                foreach($op_books->get() as $op_book){
                    $opened_books[] = $op_book->book_id;
                }
            }

            $books_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $books = Book::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($books as $book){
                if(count($books_id) < $first_limit){
                    $categories = explode(',', $book->categories);
                    if(in_array($cat_id, $categories) and !in_array($book->id, $opened_books)){
                        $books_id[] = $book;
                        $ids[] = $book->id;
                    }
                } else {
                    break;
                }
            }


            if(count($books_id) < $first_limit){
                foreach($opened_books as $opened_book){
                    if(count($books_id) < $first_limit){
                        $book = Book::find($opened_book);
                        if(!empty($book) && ($book->status == 1) && ($book->subscription_level <= $level)){
                            $categories = explode(',', $book->categories);
                            if(in_array($cat_id, $categories)){
                                $books_id[] = $book;
                                $ids[] = $book->id;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }

            $counted = count($books_id);
            if(($counted < $limit) && (Book::where('status', 1)->where('subscription_level', '<=', $level)->count() >= $limit)){
                $other_books = Book::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($books_id)){
                    foreach($books_id as $book_id){
                        $other_books = $other_books->where('id', '<>', $book_id->id);
                    }
                }
                $other_books = $other_books->inRandomOrder();
                if($other_books->count() > 0){
                    $other_books = $other_books->get(['id', 'slug']);
                    foreach($other_books as $other_book){
                        if(count($books_id) < $limit){
                            if(!in_array($other_book->id, $opened_books)){
                                $books_id[] = $other_book;
                                $ids[] = $other_book->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($books_id) < $limit){
                        foreach($other_books as $other_book){
                            if(count($books_id) < $limit){
                                if(!in_array($other_book->id, $ids)){
                                    $books_id[] = $other_book;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            if(!empty($books_id)){
                foreach($books_id as $book){
                    RecommendedBook::create([
                        'user_id' => $user_id,
                        'book_id' => $book->id,
                        'slug' => $book->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }
}
