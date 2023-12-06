<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SelfReflectionOption;
use App\Models\SelfReflectionCategory;
use App\Models\SelfReflectionQuestion;
use App\Http\Requests\SelfReflectionAnswerRequest;
use App\Models\SelfReflectionAnswer;

class SelfReflectionController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    private static function category(SelfReflectionCategory $category) : SelfReflectionCategory
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

    private static function question(SelfReflectionQuestion $question) : SelfReflectionQuestion
    {
        $options = [];
        if($question->question_type == 'multiple_choice'){
            $quest_options = SelfReflectionOption::where('question_id', $question->id);
            if($quest_options->count() > 0){
                foreach($quest_options->get() as $option){
                    $options[] = $option->option;
                }
            }
        }
        $question->options = $options;
        return $question;
    }

    public function index(){
        $categories = SelfReflectionCategory::orderBy('category', 'asc');
        if($categories->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Self Reflection added yet',
                'data' => []
            ], 200);
        }
        $categories = $categories->get();
        foreach($categories as $category){
            $category = self::category($category);
        }

        return response([
            'status' => 'success',
            'message' => 'Self Reflections fetched successfully',
            'data' => $categories
        ], 200);
    }

    public function show($slug){
        $category = SelfReflectionCategory::where('slug', $slug)->first();
        if(empty($category)){
            return response([
                'status' => 'failed',
                'message' => 'No Self Reflection was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Self Reflection fetched successfully',
            'data' => self::category($category)
        ], 200);
    }

    public function answer_reflection(SelfReflectionAnswerRequest $request, $slug){
        $category = SelfReflectionCategory::where('slug', $slug)->first();
        if(empty($category)){
            return response([
                'status' => 'failed',
                'message' => 'No Self Reflection was fetched'
            ], 404);
        }

        $answers = [];
        foreach($request->answers as $answer){
            $answers[] = [
                'question' => SelfReflectionQuestion::find($answer['question_id'])->question,
                'answer' => $answer['answer']
            ];
        }

        $answer = SelfReflectionAnswer::create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'answers' => json_encode(array_values($answers))
        ], 200);

        $answer->answers = json_decode($answer->answers);

        return response([
            'status' => 'success',
            'message' => 'Self Reflection answered successfuly',
            'data' => $answer
        ], 200);
    }

    public function previous_answers(){
        $slug = !empty($_GET['slug']) ? (string)$_GET['slug'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $answers = SelfReflectionAnswer::where('user_id', $this->user->id);
        if(!empty($slug)){
            $category = SelfReflectionCategory::where('slug', $slug)->first();
            if(!empty($category)){
                $answers = $answers->where('category_id', $category->id);
            }
        }
        $answers = $answers->orderBy('created_at', 'desc');

        $answers = $answers->paginate($limit);
        if(!empty($answers)){
            foreach($answers as $answer){
                $answer->answers = json_decode($answer->answers);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Previous Answers fetched successfully',
            'data' => $answers
        ], 200);
    }
}
