<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerDailyQuestionRequest;
use App\Models\Category;
use App\Models\DailyQuestion;
use App\Models\DailyQuestionAnswer;
use App\Models\DailyQuestionOption;
use Illuminate\Http\Request;

class DailyQuestionController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public function fetch_questions(){
        $answered = DailyQuestionAnswer::where('answer_date', date('Y-m-d'));
        if($answered->count() < 1){
            $fetch = true;
        } else {
            $fetch = false;
        }

        if(!$fetch){
            return response([
                'status' => 'failed',
                'message' => 'Already answered for today'
            ], 409);
        }

        $questions = DailyQuestion::orderBy('created_at', 'asc');
        if($questions->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Question was fetched'
            ], 409);
        }

        $questions = $questions->get(['id', 'question']);
        foreach($questions as $question){
            $question->options = DailyQuestionOption::where('daily_question_id', $question->id)->orderBy('value', 'asc')->get(['id', 'option', 'value']);
        }

        return response([
            'status' => 'success',
            'message' => 'Questions fetched successfully',
            'data' => $questions
        ], 200);
    }

    public function answer_questions(AnswerDailyQuestionRequest $request){
        $answered = DailyQuestionAnswer::where('answer_date', date('Y-m-d'));
        if($answered->count() < 1){
            $fetch = true;
        } else {
            $fetch = false;
        }

        if(!$fetch){
            return response([
                'status' => 'failed',
                'message' => 'Already answered for today'
            ], 409);
        }

        $category_scores = [];
        $answers = [];

        foreach($request->answers as $answer){
            $question = DailyQuestion::find($answer['question_id']);
            $option = DailyQuestionOption::find($answer['option_id']);

            $ans = [
                'question_id' => $question->id,
                'question' => $question->question,
                'answer' => $option->option,
                'value' => $option->value
            ];

            $answers[] = $ans;

            $categories = explode(',', $question->categories);
            foreach($categories as $id){
                $category = Category::find(trim($id));
                if(!empty($category)){
                    if(isset($catgoey_scores[$category->id])){
                        $category_scores[$category->id] += $option->value;
                    } else {
                        $category_scores[$category->id] = $option->value;
                    }
                }
            }
        }

        $categ_scores = [];
        foreach($category_scores as $key=>$value){
            $category = Category::find($key);
            $categ_scores[] = [
                'category_id' => $category->id,
                'category' => $category->category,
                'value' => $value
            ];
        }

        $answer_summary = DailyQuestionAnswer::create([
            'user_id' => $this->user->id,
            'answer_date' => date('Y-m-d'),
            'answers' => json_encode($answers),
            'category_scores' => json_encode($categ_scores),
            'computed' => 0
        ]);

        $answer_summary->answers = $answers;
        $answer_summary->category_scores = $categ_scores;

        self::log_activity($this->user->id, "checkin", "daily_question_answers", $answer_summary->id);

        return response([
            'status' => 'success',
            'message' => 'Daily Question successfully answered',
            'data' => $answer_summary
        ], 200);
    }
}
