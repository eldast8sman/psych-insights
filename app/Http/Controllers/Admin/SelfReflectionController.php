<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SelfReflectionOption;
use App\Models\SelfReflectionCategory;
use App\Models\SelfReflectionQuestion;
use App\Http\Requests\Admin\StoreSelfReflectionOptionRequest;
use App\Http\Requests\Admin\StoreSelfReflectionCategoryRequest;
use App\Http\Requests\Admin\StoreSelfReflectionQuestionRequest;
use App\Http\Requests\Admin\UpdateSelfReflectionQuestionRequest;

class SelfReflectionController extends Controller
{
    private $user;
    private $file_disk = 'public';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public static function category(SelfReflectionCategory $category) : SelfReflectionCategory
    {
        $questions = SelfReflectionQuestion::where('category_id', $category->id);
        if($questions->count() > 0){
            $questions = $questions->get();
            foreach($questions as $question){
                $question = self::question($question);
            }
            $category->questions = $questions;
        } else {
            $category->questions = [];
        }
        return $category;
    }

    public static function question(SelfReflectionQuestion $question) : SelfReflectionQuestion
    {
        if($question->question_type == 'multiple_choice'){
            $question->options = SelfReflectionOption::where('question_id', $question->id)->get();
        }
        return $question;
    }

    public function index(){
        $categories = SelfReflectionCategory::orderBy('category', 'asc')->get();
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
            'message' => 'Self Reflection fetched successfully',
            'data' => $categories
        ], 200);
    }

    public function store(StoreSelfReflectionCategoryRequest $request){
        $old = SelfReflectionCategory::where('category', $request->category);
        if($old->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'There is already a Category with this name'
            ], 409);
        }

        $all = $request->all();
        $all['pubished'] = 0;
        if(!$category = SelfReflectionCategory::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Category not created'
            ], 409);
        }

        return response([
            'status' => 'success',
            'message' => 'Category Added successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function show(SelfReflectionCategory $category){
        return response([
            'status' => 'success',
            'message' => 'Self Reflection fetched successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function store_question(StoreSelfReflectionQuestionRequest $request, SelfReflectionCategory $category){
        $data = [
            'question' => $request->question,
            'question_type' => $request->question_type,
            'category_id' => $category->id
        ];

        if(!$question = SelfReflectionQuestion::create($data)){
            return response([
                'status' => 'failed',
                'message' => 'Question Upload failed'
            ], 500);
        }

        if(isset($request->options) and !empty($request->options)){
            foreach($request->options as $option){
                SelfReflectionOption::create([
                    'question_id' => $question->id,
                    'option' => $option
                ]);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Question uploaded successfully',
            'data' => self::question($question)
        ], 200);
    }

    public function show_question(SelfReflectionQuestion $question){
        $question = self::question($question);

        return response([
            'status' => 'success',
            'message' => 'Question fetched successfully',
            'data' => $question
        ], 200);
    }

    public function store_option(StoreSelfReflectionOptionRequest $request, SelfReflectionQuestion $question){
        SelfReflectionOption::create([
            'option' => $request->option,
            'question_id' => $question->id
        ]);

        return response([
            'status' => 'success',
            'message' => 'Option successfully added',
            'data' => self::question($question)
        ], 200);
    }

    public function update_option(StoreSelfReflectionOptionRequest $request, SelfReflectionOption $option){
        $option->option = $request->option;
        $option->save();

        $question = SelfReflectionQuestion::find($option->question_id);
        return response([
            'status' => 'success',
            'message' => 'Option updated successfully',
            'data' => self::question($question)
        ], 200);
    }

    public function update_question(UpdateSelfReflectionQuestionRequest $request, SelfReflectionQuestion $question){
        $question->update($request->all());

        return response([
            'status' => 'success',
            'message' => 'Question updated successfully',
            'data' => self::question($question)
        ], 200);
    }

    public function update(StoreSelfReflectionCategoryRequest $request, SelfReflectionCategory $category){
        $category->update($request->all());

        return response([
            'status' => 'success',
            'message' => 'Self Reflection Category updated successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function publish(SelfReflectionCategory $category){
        $category->published = ($category->published == 0) ? 1 : 0;
        $category->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::category($category)
        ], 200);
    }

    public function destroy_option(SelfReflectionOption $option){
        $option->delete();

        return response([
            'status' => 'success',
            'message' => 'Option deleted successfully',
        ], 200);
    }

    public function destroy_question(SelfReflectionQuestion $question){
        $question->delete();

        $options = SelfReflectionOption::where('question_id', $question->id);
        if($options->count() > 0){
            foreach($options->get() as $option){
                $option->delete();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Question deleted successfully',
        ], 200);
    }

    public function destroy(SelfReflectionCategory $category){
        $category->delete();

        $questions = SelfReflectionQuestion::where('category_id', $category->id);
        if($questions->count() > 0){
            foreach($questions->get() as $question){
                $question->delete();

                $options = SelfReflectionOption::where('question_id', $question->id);
                if($options->count() > 0){
                    foreach($options as $option){
                        $option->delete();
                    }
                }  
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Self Reflection Category deleted successfully'
        ], 200);
    }
}
