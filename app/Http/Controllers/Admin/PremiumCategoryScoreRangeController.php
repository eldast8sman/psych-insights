<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PremiumCategoryScoreRange;
use App\Http\Requests\Admin\StorePremiumCategoryScoreRange;

class PremiumCategoryScoreRangeController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function index()
    {
        $categories = Category::where('premium_category', 1)->orderBy('category', 'asc');
        if($categories->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Premium Category was fetched'
            ], 404);
        }
        $ranges = [];
        foreach($categories->get() as $category){
            $range = [
                'category' => $category->category
            ];
            $range['ranges'] = PremiumCategoryScoreRange::where('category_id', $category->id)->orderBy('min', 'asc')->orderBy('max', 'asc')->get();
            $ranges[] = $range;
        }

        return response([
            'status' => 'success',
            'message' => 'Distrss Score Range fetched successfully',
            'data' => $ranges
        ], 200);
    }

    public function store(StorePremiumCategoryScoreRange $request)
    {
        $category = Category::find($request->category_id);
        if($category->premium_category != 1){
            return response([
                'status' => 'failed',
                'message' => 'Not a Premium Category'
            ], 409);
        }

        $min_check = PremiumCategoryScoreRange::where('category_id', $request->category_id)->where('min', '<=', $request->min)->where('max', '>=', $request->min);
        $max_check = PremiumCategoryScoreRange::where('category_id', $request->category_id)->where('min', '<=', $request->max)->where('max', '>=', $request->max);
        if(($min_check->count() > 0) or ($max_check->count() > 0)){
            return response([
                'status' => 'failed',
                'message' => 'A Score range CANNOT overlap another score range'
            ]);
        }

        if($request->min > $request->max){
            return response([
                'status' => 'failed',
                'message' => 'Min cannot be higher than Max'
            ], 409);
        }

        if(!$range = PremiumCategoryScoreRange::create($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Distress Score Range Upload Failed'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Distress Score Range Upload successful',
            'data' => $range
        ], 200);
    }

    public function fetch_by_category(Category $category){
        $ranges = PremiumCategoryScoreRange::where('category_id', $category->id)->orderBy('min', 'asc')->orderBy('max', 'asc')->get();

        return response([
            'status' => 'success',
            'message' => 'Distress Score Ranges fetched successfully',
            'data' => $ranges
        ], 200);
    }

    public function show(PremiumCategoryScoreRange $range)
    {
        return response([
            'status' => 'suceess',
            'message' => 'Distress Score Range fetched successfully',
            'data' => $range
        ], 200);
    }

    public function update(StorePremiumCategoryScoreRange $request, PremiumCategoryScoreRange $range)
    {
        $category = Category::find($request->category_id);
        if($category->premium_category != 1){
            return response([
                'status' => 'failed',
                'message' => 'Not a Premium Category'
            ], 409);
        }

        $min_check = PremiumCategoryScoreRange::where('category_id', $request->category_id)->where('min', '<=', $request->min)->where('max', '>=', $request->min)->where('id', '<>', $range->id);
        $max_check = PremiumCategoryScoreRange::where('category_id', $request->category_id)->where('min', '<=', $request->max)->where('max', '>=', $request->max)->where('id', '<>', $range->id);
        if(($min_check->count() > 0) or ($max_check->count() > 0)){
            return response([
                'status' => 'failed',
                'message' => 'A Score range CANNOT overlap another score range'
            ]);
        }

        if($request->min > $request->max){
            return response([
                'status' => 'failed',
                'message' => 'Min cannot be higher than Max'
            ], 409);
        }

        if(!$range->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Distress Score Range update Failed'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Distress Score Range updated successful',
            'data' => $range
        ], 200);
    }

    public function destroy(PremiumCategoryScoreRange $range)
    {
        $range->delete();

        return response([
            'status' => 'failed',
            'message' => 'Distress Score Range deleted successfully'
        ], 409);
    }
}
