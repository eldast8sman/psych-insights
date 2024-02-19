<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGoalCategoryRequest;
use App\Http\Requests\Admin\StoreGoalPlanQuestionRequest;
use App\Http\Requests\Admin\StoreGoalReflectionRequest;
use App\Http\Requests\Admin\UpdateGoalCategoryRequest;
use App\Http\Requests\Admin\UpdateGoalPlanQuestionRequest;
use App\Http\Requests\Admin\UpdateGoalReflectionRequest;
use App\Models\GoalCategory;
use App\Models\GoalPlanQuestions;
use App\Models\GoalReflection;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    private $user;
    private $file_disk = 's3';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public static function category(GoalCategory $category) : GoalCategory
    {
        $category->reflections = GoalReflection::where('goal_category_id', $category->id)->get();
        $category->plan_questions = GoalPlanQuestions::where('goal_category_id', $category->id)->get();
        return $category;
    }

    public function index(){
        $categories = GoalCategory::orderBy('category', 'asc')->get();
        if(empty($categories)){
            return response([
                'status' => 'success',
                'message' => 'No Category was fetched',
                'data' => []
            ], 200);
        }

        foreach($categories as $category){
            $category = self::category($category);
        }

        return response([
            'status' => 'success',
            'message' => 'Goal Categories fetched successfully',
            'data' => $categories
        ], 200);
    }

    public function store(StoreGoalCategoryRequest $request){
        $all = $request->all();
        if(!$category = GoalCategory::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Could not create Goal Category'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Category added successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function store_reflections(StoreGoalReflectionRequest  $request, GoalCategory $category){
        $reflections = $request->reflections;
        foreach($reflections as $reflection){
            if(($reflection['type'] == 'range') and ($reflection['max'] < $reflection['min'])){
                continue;
            }
            if(($reflection['type'] != 'range') and ($reflection['type'] != 'text')){
                continue;
            }

            $reflection['goal_category_id'] = $category->id;

            GoalReflection::create($reflection);
        }

        return response([
            'status' => 'success',
            'message' => 'Reflections added successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function store_goal_questions(StoreGoalPlanQuestionRequest $request, GoalCategory $category){
        $questions = $request->goal_questions;
        foreach($questions as $question){
            $question['goal_category_id'] = $category->id;
            GoalPlanQuestions::create($question);
        }

        return response([
            'status' => 'success',
            'message' => 'Goal Plan Questions uploaded successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function update_reflection(UpdateGoalReflectionRequest $request, GoalReflection $reflection){
        if(!$reflection->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Update Failed'
            ], 409);
        }

        return response([
            'status' => 'success',
            'message' => 'Reflection Updated successfully',
            'data' => $reflection
        ], 200);
    }

    public function update_goal_question(UpdateGoalPlanQuestionRequest $request, GoalPlanQuestions $question){
        if(!$question->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Update Failed'
            ], 409);
        }

        return response([
            'status' => 'success',
            'message' => 'Goal Plan Question Updated successfully',
            'data' => $question
        ], 200);
    }

    public function destroy_reflection(GoalReflection $reflection){
        $reflection->delete();

        return response([
            'status' => 'success',
            'message' => 'Reflection deleted successfully',
            'data' => $reflection
        ], 200);
    }

    public function destroy_goal_question(GoalPlanQuestions $question){
        $question->delete();

        return response([
            'status' => 'success',
            'message' => 'Goal Plan Question successfully deleted',
            'data' => $question
        ], 200);
    }

    public function show(GoalCategory $category){
        return response([
            'status' => 'success',
            'message' => 'Goal Category fetched successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function update(UpdateGoalCategoryRequest $request, GoalCategory $category){
        if(!$category->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Update Failed'
            ], 422);
        }

        return response([
            'status' => 'success',
            'message' => 'Update successful',
            'data' => self::category($category)
        ], 200);
    }

    public function publish(GoalCategory $category){
        $category->published = ($category->published == 0) ? 1 : 0;
        $category->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::category($category)
        ], 200);
    }

    public function destroy(GoalCategory $category){
        $reflections = GoalReflection::where('goal_category_id', $category->id)->get();
        if(!empty($reflections)){
            foreach($reflections as $reflection){
                $reflection->delete();
            }
        }
        $questions = GoalPlanQuestions::where('goal_category_id', $category->id)->get();
        if(!empty($questions)){
            foreach($questions as $question){
                $question->delete();
            }
        }
        $category->delete();

        return response([
            'status' => 'success',
            'message' => 'Goal Category deleted successfully'
        ], 200);
    }
}
