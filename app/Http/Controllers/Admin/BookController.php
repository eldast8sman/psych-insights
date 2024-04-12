<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\FileManager;
use App\Models\OpenedBook;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;

class BookController extends Controller
{
    private $user;
    private $file_disk = 's3';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = !empty($_GET['search']) ? $_GET['search'] : "";
        $filter = isset($_GET['status']) ? (int)$_GET['status'] : NULL;
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : 'asc';
        $from = !empty($_GET['from']) ? (string)$_GET['from'] : "";
        $to = !empty($_GET['to']) ? (string)$_GET['to'] : "";
        $sort_by = !empty($_GET['sort_by']) ? (string)$_GET['sort_by'] : 'title';
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $books = Book::where('status', '>=', 0);
        if(!empty($search)){
            $books = $books->where('title', 'like', '%'.$search.'%');
        }
        if($filter !== NULL){
            $books = $books->where('status', $filter);
        }
        if(!empty($from)){
            $books = $books->where('publication_year', '>=', $from);
        }
        if(!empty($to)){
            $books = $books->where('publication_year', '<=', $to);
        }
        if((($sort_by == 'title') || ($sort_by == 'publication_year')) && (($sort == 'asc') || ($sort == 'desc'))){
            $books = $books->orderBy($sort_by, $sort);
        }

        if($books->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched',
                'data' => null
            ], 200);
        }

        $books = $books->paginate($limit);
        foreach($books as $book){
            $book = self::book($book);
        }

        return response([
            'status' => 'failed',
            'message' => 'Books fetched successfully',
            'data' => $books
        ], 200);
    }

    public function summary(){
        $total_books = Book::count();
        $total_views = OpenedBook::get()->sum('frequency');
        $popular_books = Book::orderBy('favourite_count', 'desc')->orderBy('opened_count', 'desc')->limit(5)->get();

        foreach($popular_books as $book){
            $book = self::book($book);
        }

        $data = [
            'total_books' => number_format($total_books),
            'total_views' => number_format($total_views),
            'popular_books' => $popular_books
        ];

        return response([
            'status' => 'success',
            'message' => 'Book Summary fetched',
            'data' => $data
        ], 200);
    }

    public static function book(Book $book) : Book
    {
        if(!empty($book->book_cover)){
            $book->photo = FileManagerController::fetch_file($book->book_cover);
            unset($book->book_cover);
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

        $sub_level = $book->subscription_level;
        if($sub_level > 0){
            $package = SubscriptionPackage::where('level', $sub_level)->first();
            $subscription_level = $package->package;
        } else {
            $subscription_level = "Basic";
        }

        $book->subscription_level = $subscription_level;

        return $book;
    }

    public function store(StoreBookRequest $request)
    {
        $all = $request->except(['book_cover', 'categories']);
        if(!empty($request->book_cover)){
            if(!$upload = FileManagerController::upload_file($request->book_cover, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Book Cover could not be uploaded'
                ], 500);
            }

            $all['book_cover'] = $upload->id;
        }
        $all['categories'] = join(',', $request->categories);
        if(!$book = Book::create($all)){
            if(isset($all['book_cover']) && !empty($all['book_cover'])){
                FileManagerController::delete($all['book_cover']);
            }
            return response([
                'status' => 'failed',
                'message' => 'Book upload failed'
            ], 500);
        }

        $book = self::book($book);

        return response([
            'status' => 'failed',
            'message' => 'Book upload was successful',
            'data' => $book
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        return response([
            'status' => 'success',
            'message' => 'Book successfully fetched',
            'data' => self::book($book)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $all = $request->except(['categories', 'book_cover']);
        if(!empty($request->book_cover)){
            if(!$upload = FileManagerController::upload_file($request->book_cover, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Book Cover could not be uploaded'
                ], 500);
            }

            $all['book_cover'] = $upload->id;
            $old_cover = $book->book_cover;
        }
        $categories = [];
        foreach($request->categories as $cat_id){
            $category = Category::where('id', trim($cat_id))->orWhere('category', trim($cat_id))->first();
            if(!empty($category)){
                if(!in_array($category->id, $categories)){
                    $categories[] = $category->id;
                }
            }
        }
        $all['categories'] = join(',', $categories);
        if(!$book->update($all)){
            if(isset($all['book_cover']) && !empty($all['book_cover'])){
                FileManagerController::delete($all['book_cover']);
            }
            return response([
                'status' => 'failed',
                'message' => 'Book Update failed'
            ], 500);
        }
        $book->update_dependencies();
        if(isset($old_cover)){
            FileManagerController::delete($old_cover);
        }

        return response([
            'status' => 'success',
            'message' => 'Book updated successfully',
            'data' => self::book($book) 
        ], 200);
    }

    public function activation(Book $book){
        $book->status = ($book->status == 0) ? 1 : 0;
        $book->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::book($book)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();
        if(!empty($book->book_cover)){
            FileManagerController::delete($book->book_cover);
        }

        return response([
            'status' => 'success',
            'message' => 'Book deleted successfully'
        ], 200);
    }
}
