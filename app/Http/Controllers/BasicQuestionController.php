<?php

namespace App\Http\Controllers;

use App\Models\BasicQuestion;
use App\Models\BasicQuestionOption;
use App\Models\BasicQuestionSpecialOption;
use App\Models\PrerequisiteQuestion;
use App\Models\QuestionAnswerSummary;
use Illuminate\Http\Request;

class BasicQuestionController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public function fetch_questions(){
        $last_answer = QuestionAnswerSummary::where('user_id', $this->user->id);
        $fetch = false;
        if($last_answer->count() < 1){
            $fetch = true;
        } else {
            $last_answer = $last_answer->first();
            $today = date('Y-m-d');

            if($today >= $last_answer->next_question){
                $fetch = true;
            }
        }

        if(!$fetch){
            return response([
                'status' => 'failed',
                'message' => 'It\'s not yet time to answer this questionnaire'
            ], 409);
        }

        $questions = BasicQuestion::orderBy('created_at', 'asc');
        if($questions->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Question was fetched'
            ], 404);
        }

        $questions = $questions->get(['id', 'question', 'special_options']);
        foreach($questions as $question){
            $question->has_prerequisiste = PrerequisiteQuestion::where('basic_question_id', $question->id)->first(['prerequisite_id', 'prerequisite_value', 'action', 'default_value']);
            if($question->special_options == 1){
                $options = BasicQuestionSpecialOption::where('basic_question_id', $question->id)->orderBy('value', 'desc')->get(['id', 'option', 'value']);
            } else {
                $options = BasicQuestionOption::orderBy('value', 'desc')->get(['id', 'option', 'value']);
            }
            $question->options = $options;
            unset($question->special_options);
        }

        return response([
            'status' => 'success',
            'message' => 'Questions successfuly fetched',
            'data' => $questions
        ], 200);
    }

    public function answer_basic_question(){

    }
}
