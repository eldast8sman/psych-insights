<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Models\BlogCategory;
use App\Models\BlogCategoryBlog;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function index(){
        $categories = BlogCategory::orderBy('category', 'asc');
        if($categories->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Blog Category has been added',
                'data' => null
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Blog Categories fetched successfully',
            'data' => $categories->get()
        ], 200);
    }

    public function store(StoreBlogCategoryRequest $request){
        if(BlogCategory::where('category', $request->category)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'This Category already exists'
            ], 409);
        }

        if(!$category = BlogCategory::create($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Category was not added'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Blog Category added successfully',
            'data' => $category
        ], 200);
    }

    public function show(BlogCategory $category){
        return response([
            'status' => 'success',
            'message' => 'Blog Category fetched successfully',
            'data' => $category
        ], 200);
    }

    public function update(StoreBlogCategoryRequest $request, BlogCategory $category){
        if(BlogCategory::where('category', $request->category)->where('id', '<>', $category->id)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'This Category already exists'
            ], 409);
        }

        if(!$category->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Category was not updated'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Blog Category updated successfully',
            'data' => $category
        ], 200);
    }

    public function destroy(BlogCategory $category){
        $category->delete();

        $cats = BlogCategoryBlog::where('blog_category_id', $category->id);
        if($cats->count() > 0){
            foreach($cats->get() as $cat){
                $cat->delete();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Blog Category deleted successfully'
        ], 200);
    }
}
