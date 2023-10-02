<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\Category;
use App\Models\OpenedBook;
use Illuminate\Http\Request;
use App\Models\RecommendedBook;
use App\Models\FavouriteResource;
use App\Models\CurrentSubscription;

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
            if(($counted < $limit) && (Book::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
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

    public static function fetch_book(Book $book) : Book
    {
        if(!empty($book->book_cover)){
            $book->book_cover = FileManagerController::fetch_file($book->book_cover);
        }

        if(!empty($book->categories)){
            $categories = [];

            $categs = explode(',', $book->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $book->categories = $categories;
        }

        unset($book->id);
        unset($book->created_at);
        unset($book->updated_at);

        return $book;
    }

    public function recommended_books(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_books = RecommendedBook::where('user_id', $this->user->id);
        if($rec_books->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Recommended Book',
                'data' => []
            ], 200);
        }

        $books = [];
        $rec_books = $rec_books->get();
        if(empty($search)){
            foreach($rec_books as $rec_book){
                $book = Book::find($rec_book->book_id);
                if(!empty($book) && ($book->status == 1)){
                    $books[] = $this->fetch_book($book);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_books as $rec_book){
                $book = Book::find($rec_book->book_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($book->title, $word) !== FALSE) or (strpos($book->summary, $word) !== FALSE) or (strpos($book->author, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$book->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $book = Book::find($key);
                    if(!empty($book) and ($book->status == 1)){
                        $books[] = $this->fetch_book($book);
                    }
                }
            }
        }

        if(empty($books)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was found',
                'data' => []
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Books fetched successfully',
            'data' => self::paginate_array($books, $limit, $page)
        ], 200);
    }

    public function recommended_book($slug){
        $rec_book = RecommendedBook::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_book)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched'
            ], 404);
        }

        $book = Book::find($rec_book->book_id);
        if(empty($book) or ($book->status != 1)){
            return response([
                'ststus' => 'failed',
                'message' => 'No Book was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Book fetched successfully',
            'data' => $this->fetch_book($book)
        ], 200);
    }

    public function mark_as_opened($slug){
        $book = Book::where('slug', $slug)->first();
        if(empty($book)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched'
            ], 404);
        }

        $opened = OpenedBook::where('user_id', $this->user->id)->where('book_id', $book->id)->first();
        if(empty($opened)){
            OpenedBook::create([
                'user_id' => $this->user->id,
                'book_id' => $book->id,
                'frequency' => 1
            ]);
        } else {
            $opened->frequency += 1;
            $opened->save();
        }
        $book->opened_count += 1;
        $book->save();

        return response([
            'status' => 'success',
            'message' => 'Marked as Opened'
        ], 200);
    }

    public function opened_books(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }
        
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $opened_books = OpenedBook::where('user_id', $this->user->id);
        if($opened_books->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Book',
                'data' => []
            ], 200);
        }

        $books = [];
        $opened_books = $opened_books->get();
        if(empty($search)){
            foreach($opened_books as $opened_book){
                $book = Book::find($opened_book->book_id);
                if(!empty($book) && ($book->status == 1)){
                    $books[] = $this->fetch_book($book);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_books as $opened_book){
                $book = Book::find($opened_book->book_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($book->title, $word) !== FALSE) or (strpos($book->summary, $word) !== FALSE) or (strpos($book->author, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$book->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $book = Book::find($key);
                    if(!empty($book) and ($book->status == 1)){
                        $books[] = $this->fetch_book($book);
                    }
                }
            }
        }

        if(empty($books)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was found',
                'data' => []
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Books fetched successfully',
            'data' => self::paginate_array($books, $limit, $page)
        ], 200);
    }

    public function opened_book($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $book = Book::where('slug', $slug)->first();
        if(empty($book) or ($book->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched'
            ], 404);
        }

        $opened = OpenedBook::where('book_id', $book->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Book fetched successfully',
            'data' => $this->fetch_book($book)
        ], 200);
    }

    public function book_favourite($slug){
        $book = Book::where('slug', $slug)->first();
        if(empty($book)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched'
            ], 404);
        }

        $action = self::favourite_resource('book', $this->user->id, $book->id);
        if($action == 'saved'){
            $book->favourite_count += 1;
        } else {
            $book->favourite_count -= 1;
        }
        $book->save();
        $message = ($action == 'saved') ? 'Book added to Favourites' : 'Book removed from Favourites';

        return response([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function favourite_books(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $fav_books = FavouriteResource::where('type', 'book')->where('user_id', $this->user->id);
        if($fav_books->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Book',
                'data' => []
            ], 200);
        }

        $books = [];
        $fav_books = $fav_books->get();
        if(empty($search)){
            foreach($fav_books as $fav_book){
                $book = Book::find($fav_book->resource_id);
                if(!empty($book) && ($book->status == 1)){
                    $books[] = $this->fetch_book($book);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_books as $fav_book){
                $book = Book::find($fav_book->resource_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($book->title, $word) !== FALSE) or (strpos($book->summary, $word) !== FALSE) or (strpos($book->author, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$book->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $book = Book::find($key);
                    if(!empty($book) and ($book->status == 1)){
                        $books[] = $this->fetch_book($book);
                    }
                }
            }
        }

        if(empty($books)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was found',
                'data' => []
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Books fetched successfully',
            'data' => self::paginate_array($books, $limit, $page)
        ], 200);
    }

    public function favourite_book($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $book = Book::where('slug', $slug)->first();
        if(empty($book) or ($book->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched'
            ], 404);
        }

        $fav_book = FavouriteResource::where('resource_id', $book->id)->where('user_id', $this->user->id)->where('type', 'book')->first();
        if(empty($fav_book)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Book fetched successfully',
            'data' => $this->fetch_book($book)
        ], 200);
    }
}
