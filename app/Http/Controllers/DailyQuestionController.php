<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerDailyQuestionRequest;
use App\Models\Category;
use App\Models\DailyQuestion;
use App\Models\DailyQuestionAnswer;
use App\Models\DailyQuestionOption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyQuestionController extends Controller
{
    private $user;
    private $time;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
        if(empty($this->user->last_timezone)){
            $this->time = Carbon::now();
        } else {
            $this->time = Carbon::now($this->user->last_timezone);
        }
    }

    public function fetch_questions(){
        $answered = DailyQuestionAnswer::where('user_id', $this->user->id)->where('answer_date', date('Y-m-d'));
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
        $answered = DailyQuestionAnswer::where('user_id', $this->user->id)->where('answer_date', date('Y-m-d'));
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
        $category_total = [];
        $answers = [];

        foreach($request->answers as $answer){
            $question = DailyQuestion::find($answer['question_id']);
            $option = DailyQuestionOption::find($answer['option_id']);
            $highest_value = DailyQuestionOption::where('daily_question_id', $question->id)->orderBy('value', 'desc')->first()->value;

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
                    if(!isset($category_total[$category->id])){
                        $category_total[$category->id] = 0;
                    }
                    $category_total[$category->id] += $highest_value;
                }
            }
        }

        $categ_scores = [];
        foreach($category_scores as $key=>$value){
            $category = Category::find($key);
            $categ_scores[] = [
                'category_id' => $category->id,
                'category' => $category->category,
                'value' => $value,
                'highest_value' => $category_total[$key]
            ];
        }



        $answer_summary = DailyQuestionAnswer::create([
            'user_id' => $this->user->id,
            'answer_date' => $this->time->format('Y-m-d'),
            'answers' => json_encode($answers),
            'category_scores' => json_encode($categ_scores),
            'computed' => 0
        ]);

        $answer_summary->answers = $answers;
        $answer_summary->category_scores = $categ_scores;

        $tommorow_time = $this->time->addDay();
        $user = User::find($this->user->id);
        $user->next_daily_question = $tommorow_time->format('Y-m-d');
        $user->save();

        self::log_activity($this->user->id, "checkin", "daily_question_answers", $answer_summary->id);

        return response([
            'status' => 'success',
            'message' => 'Daily Question successfully answered',
            'data' => $answer_summary
        ], 200);
    }
}
