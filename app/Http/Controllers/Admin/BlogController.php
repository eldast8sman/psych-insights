<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\StoreBlogRequest;
use App\Http\Requests\Admin\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogCategoryBlog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    private $user;
    private $file_disk = 'public';


    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    private static function blog(Blog $blog): Blog
    {
        $categs = [];
        if(!empty($blog->categories)){
            $categories = explode(',', $blog->categories);
            foreach($categories as $category){
                $categs[] = BlogCategory::find($category)->category;
            }
        }
        $blog->categories = $categs;
        if(!empty($blog->photo)){
            $blog->photo = FileManagerController::fetch_file($blog->photo);
        }
        return $blog;
    }

    public function index(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $blogs = Blog::orderBy('created_at', 'desc');
        if(!empty($search)){
            $blogs = $blogs->where('title', 'like', '%'.$search.'%');
        }

        if($blogs->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Blog has been added yet',
                'data' => null
            ], 200);
        }

        $blogs = $blogs->paginate($limit);
        foreach($blogs as $blog){
            $blog = self::blog($blog);
        }

        return response([
            'status' => 'success',
            'message' => 'Blogs fetched successfully',
            'data' => $blogs
        ], 200);
    }

    public function store(StoreBlogRequest $request){
        $to_save = [];
        $all = $request->except(['photo', 'categories']);
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Photo Upload Failed'
                ], 500);
            }

            $all['photo'] = $upload->id;
        }
        if(!empty($request->categories)){
            $to_save = $request->categories;
            $all['categories'] = join(',', $request->categories);
        }

        if(!$blog = Blog::create($all)){
            if(isset($all['photo']) and !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Blog was not added'
            ], 409);
        }

        if(!empty($to_save)){
            foreach($to_save as $cat_id){
                BlogCategoryBlog::create([
                    'blog_category_id' => $cat_id,
                    'blog_id' => $blog->id,
                    'blog_status' => 1,
                    'blog_created' => $blog->created_at
                ]);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Blog saved successfully',
            'data' => self::blog($blog)
        ], 200);
    }

    public function show(Blog $blog){
        return response([
            'status' => 'success',
            'message' => 'Blog fetched successfully',
            'data' => self::blog($blog)
        ], 200);
    }

    public function update(UpdateBlogRequest $request, Blog $blog){
        $to_save = [];
        $all = $request->except(['photo', 'categories']);
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Photo upload failed'
                ], 409);
            }

            $all['photo'] = $upload->id;
            $old_photo = $blog->photo;
        }
        if(!empty($request->categories)){
            foreach($request->categories as $cat_id){
                $category = BlogCategory::where('id', trim($cat_id))->orWhere('category', trim($cat_id))->first();
                if(!empty($category)){
                    if(!in_array($category->id, $to_save)){
                        $to_save[] = $category->id;
                    }
                }
            }
        }
        $all['categories'] = join(',', $to_save);
        if(!$blog->update($all)){
            if(isset($all['photo']) and !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Blog Update Failed'
            ], 500);
        }
        if(isset($old_photo)){
            FileManagerController::delete($old_photo);
        }
        $old_categories = BlogCategoryBlog::where('blog_id', $blog->id);
        if($old_categories->count() > 0){
            foreach($old_categories->get() as $category){
                $category->delete();
            }
        }
        if(!empty($to_save)){
            foreach($to_save as $cat_id){
                BlogCategoryBlog::create([
                    'blog_category_id' => $cat_id,
                    'blog_id' => $blog->id,
                    'blog_created' => $blog->created_at
                ]);
            }
        }
        $blog->update_dependencies();

        return response([
            'status' => 'success',
            'message' => 'Blog updated successfully',
            'data' => self::blog($blog)
        ], 200);
    }

    public function activation(Blog $blog){
        $blog->status = ($blog->status == 1) ? 0 : 1;
        $blog->save();

        $blog->update_dependencies();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::blog($blog)
        ], 200);
    }

    public function destroy(Blog $blog){
        $blog->delete();

        if(!empty($blog->photo)){
            FileManagerController::delete($blog->photo);
        }
        $categories = BlogCategoryBlog::where('blog_id', $blog->id);
        if($categories->count() > 0){
            foreach($categories->get() as $cat){
                $cat->delete();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Blog deleted successfully'
        ], 200);
    }

    public function summary(){
        $blogs = Blog::count();
        $categories = BlogCategory::count();

        return response([
            'status' => 'success',
            'message' => 'Blog Summary fetched successfully',
            'data' => [
                'blogs' => $blogs,
                'categories' => $categories
            ]
        ], 200);
    }
}
