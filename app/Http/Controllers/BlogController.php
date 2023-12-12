<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use App\Models\BlogCategoryBlog;
use App\Models\FavouriteResource;
use App\Models\CurrentSubscription;

class BlogController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    private static function blog(Blog $blog, $user_id) : Blog
    {
        if(!empty($blog->photo)){
            $blog->photo = FileManagerController::fetch_file($blog->photo)->url;
        } else {
            $blog->photo = "";
        }
        $categories = [];
        if(!empty($blog->categories)){
            $categs = explode(',', $blog->categories);
            foreach($categs as $categ){
                $category = BlogCategory::find(trim($categ));
                if(!empty($category)){
                    $categories[] = [
                        'category' => $category->category,
                        'slug' => $category->slug
                    ];
                }
            }
        }
        $blog->categories = $categories;
        $blog->favourited = !empty(FavouriteResource::where('resource_id', $blog->id)->where('user_id', $user_id)->where('type', 'blog')->first()) ? true : false;
        return $blog;
    }

    public function categories(){
        $categories = BlogCategory::orderBy('category', 'asc');
        if($categories->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Blog Category fetched',
                'data' => null
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Blog Categories fetched successfully',
            'data' => $categories->get()
        ], 200);
    }

    public function index(){
        $limit = !empty($_GET['limit']) ? $_GET['limit'] : 10;

        $blogs = Blog::where('status', 1)->orderBy('created_at', 'desc');
        if($blogs->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Blog was fetched',
                'data' => null
            ], 200);
        }

        $blogs = $blogs->paginate($limit);
        foreach($blogs as $blog){
            $blog = self::blog($blog, $this->user->id);
        }

        return response([
            'status' => 'success',
            'message' => 'Blogs fetched successfully',
            'data' => $blogs
        ], 200);
    }

    public function byCategory($slug){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $category = BlogCategory::where('slug', $slug)->first();
        if(empty($category)){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Link'
            ], 404);
        }

        $categs = BlogCategoryBlog::where('blog_category_id', $category->id)->where('blog_status', 1)->orderBy('blog_created', 'desc');
        if($categs->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Blog was fetched for this Category',
                'data' => null
            ], 404);
        }

        $blogs = [];
        foreach($categs->get() as $categ){
            $blogs[] = Blog::find($categ->blog_id);
        }

        $blogs = self::paginate_array($blogs, $limit, $page);
        foreach($blogs as $blog){
            $blog = self::blog($blog, $this->user->id);
        }        

        return response([
            'status' => 'success',
            'message' => 'Blogs fetched successfully',
            'data' => $blogs
        ], 200);
    }

    public function show($slug){
        $blog = Blog::where('slug', $slug)->where('status', 1)->first();
        if(empty($blog)){
            return response([
                'status' => 'failed',
                'message' => 'No Blog was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Blog fetched successfully',
            'data' => self::blog($blog, $this->user->id)
        ], 200);
    }

    public function blog_favourite($slug){
        $blog = Blog::where('slug', $slug)->where('status', 1)->first();
        if(empty($blog)){
            return response([
                'status' => 'failed',
                'message' => 'No Blog was fetched'
            ], 404);
        }

        $action = self::favourite_resource('blog', $this->user->id, $blog->id);
        if($action == 'saved'){
            $blog->favourite_count += 1;
        } else {
            $blog->favourite_count -= 1;
        }
        $blog->save();

        $message = ($action == 'saved') ? 'Blog added to Favourites' : 'Blog removed from Favourites';

        return response([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function favourite_blogs(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $fav_blogs = FavouriteResource::where('type', 'blog')->where('user_id', $this->user->id)->orderBy('created_at', 'desc');
        if($fav_blogs->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Blog',
                'data' => $fav_blogs->paginate($limit)
            ], 200);
        }

        $blogs = [];
        $fav_blogs = $fav_blogs->get();
        foreach($fav_blogs as $fav_blog){
            $blog = Blog::find($fav_blog->resource_id);
            if(!empty($blog) and ($blog->status == 1)){
                $blogs[] = self::blog($blog, $this->user->id);
            }
        }

        if(empty($blogs)){
            return response([
                'status' => 'failed',
                'message' => 'No Blog was found',
                'data' => self::paginate_array($blogs, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Blog Posts fetched successfully',
            'data' => self::paginate_array($blogs, $limit, $page)
        ], 200);
    }
}
