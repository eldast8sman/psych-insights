<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller
{
    private $user;

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
        $categories = Category::orderBy('category', 'asc');
        if($categories->count() > 0){
            return response([
                'status' => 'success',
                'message' => 'Categories fetched successfully',
                'data' => $categories->get() 
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'No Category was fetched',
                'data' => null
            ], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        if($category = Category::create($request->all())){
            return response([
                'status' => 'success',
                'message' => 'Category added successfully',
                'data' => $category
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'Category creation failed'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        if(empty($category)){
            return response([
                'status' => 'failed',
                'message' => 'No Category was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Category fetched successfully',
            'data' => $category
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        if(empty($category)){
            return response([
                'status' => 'faiiled',
                'message' => 'No Category was fetched'
            ], 404);
        }

        if(Category::where('category', $request->category)->where('id', '<>', $category->id)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'There is already a Category with this name'
            ], 422);
        }

        if($category->update($request->all())){
            return response([
                'status' => 'success',
                'message' => 'Category fetched successfully',
                'data' => $category
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'Category update failed'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if(empty($category)){
            return response([
                'status' => 'failed',
                'message' => 'No Category was fetched'
            ]);
        }

        $category->delete();
        return response([
            'status' => 'success',
            'message' => 'Category deleted successfully',
            'data' => $category
        ], 200);
    }
}
