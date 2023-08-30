<?php

namespace App\Http\Controllers;

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
}
