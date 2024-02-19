<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerGoalReflectionRequest;
use App\Http\Requests\SetGoalRequest;
use App\Models\GoalCategory;
use App\Models\GoalPlanQuestions;
use App\Models\GoalReflection;
use App\Models\GoalReflectionAnswer;
use App\Models\NextGoalAnswer;
use App\Models\User;
use App\Models\UserGoalAnswer;
use App\Models\UserGoalReminder;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    private static function category(GoalCategory $category, $user_id) : GoalCategory
    {
        $category->reflections = GoalReflection::where('goal_category_id', $category->id)->get();
        $category->goal_questions = GoalPlanQuestions::where('goal_category_id', $category->id)->get();
        $next_answer = NextGoalAnswer::where('user_id', $user_id)->where('goal_category_id', $category->id)->first();
        $category->cannot_answer_until = !empty($next_answer) ? (string)$next_answer->next_date : ""; 

        return $category;
    }

    public function index(){
        $categories = GoalCategory::where('published', 1)->orderBy('category', 'asc');
        if($categories->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No published Goal yet',
                'data' => []
            ], 200);
        }
        $categories = $categories->get();
        foreach($categories as $category){
            $category = self::category($category, $this->user->id);
        }

        return response([
            'status' => 'success',
            'message' => 'Goal Categories fetched successfully',
            'data' => $categories
        ], 200);
    }

    public function show($slug){
        $category = GoalCategory::where('slug', $slug)->first();
        if(empty($category)){
            return response([
                'status' => 'failed',
                'message' => 'No Goal was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Goal fetched successfully',
            'data' => self::category($category, $this->user->id)
        ], 200);
    }

    public function answer_reflection(AnswerGoalReflectionRequest $request, $slug){
        $category = GoalCategory::where('slug', $slug)->first();
        if(empty($category) or ($category->published != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Goal was fetched'
            ], 404);
        }

        $next_answer = NextGoalAnswer::where('user_id', $this->user->id)->where('goal_category_id', $category->id)->first();
        if(!empty($next_answer)){
            if($next_answer->next_date > date('Y-m-d')){
                return response([
                    'status' => 'failed',
                    'message' => 'Cannot answer this questions from this Goal until '.$next_answer->next_date
                ], 409);
            }
        }

        $saved_answers = [];
        $answers = $request->answers;
        foreach($answers as $answer){
            $reflection = GoalReflection::where('goal_category_id', $category->id)->where('id', $answer['reflection_id'])->first();
            if(empty($reflection)){
                return response([
                    'status' => 'failed',
                    'message' => 'Invalid Reflection',
                ], 409);
            }

            $saved_answers[] = [
                'question' => $reflection->title,
                'pre_text' => $reflection->pre_text,
                'answer' => $answer['answer']
            ];
        }

        GoalReflectionAnswer::create([
            'user_id' => $this->user->id,
            'goal_category_id' => $category->id,
            'answers' => json_encode($saved_answers)
        ]);

        $next_sunday = "";
        $i = 1;
        while(empty($next_sunday)){
            $time = time() + (60 * 60 * 24 * $i);
            if(strtolower(date('l', $time)) == 'sunday'){
                $next_sunday = date('Y-m-d', $time);
            }
            $i++;
        }
        if(empty($next_answer)){
            NextGoalAnswer::create([
                'user_id' => $this->user->id,
                'goal_category_id' => $category->id,
                'reflection_answered' => 1,
                'goal_set' => 0,
                'next_date' => $next_sunday
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'Goal Reflections answered successfully',
            'data' => $saved_answers
        ], 200);
    }

    public function set_goals(SetGoalRequest $request, $slug){
        $category = GoalCategory::where('slug', $slug)->first();
        if(empty($category) or ($category->published != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Goal was fetched'
            ], 404);
        }

        $next_answer = NextGoalAnswer::where('user_id', $this->user->id)->where('goal_category_id', $category->id)->first();
        if(!empty($next_answer)){
            if(($next_answer->next_date > date('Y-m-d')) and ($next_answer->goal_set == 1)){
                return response([
                    'status' => 'failed',
                    'message' => 'Cannot answer this questions from this Goal until '.$next_answer->next_date
                ], 409);
            }
        }

        $reminder_type = isset($request->reminder_type) ? (string)$request->reminder_type : "recurring";
        if(($reminder_type != 'recurring') and ($reminder_type != 'one time')){
            return response([
                'status' => 'failed',
                'message' => 'Wrong reminder type'
            ], 422);
        }

        $saved_answers = [];
        $reminders = [];
        $answers = $request->answers;
        foreach($answers as $answer){
            $question = GoalPlanQuestions::where('goal_category_id', $category->id)->where('id', $answer['question_id'])->first();
            if(empty($question)){
                return response([
                    'status' => 'failed',
                    'message' => 'Invalid Question',
                ], 409);
            }

            if($question->weekly_plan == 1){
                if(!is_array($answer['answer'])){
                    return response([
                        'status' => 'failed',
                        'message' => 'Wrong reminder format'
                    ], 422);
                }
                foreach($answer['answer'] as $rem){
                    $reminders[] = $rem;
                }

                $saved_answers[] = [
                    'question' => $question->title,
                    'pre_text' => $question->pre_text,
                    'answer' => $answer['answer']
                ];
            } else {
                if(!is_string($answer['answer'])){
                    return response([
                        'status' => 'failed',
                        'message' => 'Wrong answer format'
                    ], 422);
                }
                $saved_answers[] = [
                    'question' => $question->title,
                    'pre_text' => $question->pre_text,
                    'answer' => $answer['answer']
                ];
            }
        }

        UserGoalAnswer::create([
            'user_id' => $this->user->id,
            'goal_category_id' => $category->id,
            'answers' => json_encode($saved_answers)
        ]);

        if(!empty($reminders)){
            foreach($reminders as $reminder){
                $next_time = "";
                $i = 1;
                while(empty($next_time)){
                    $time = time() + (60 * 60 * 24 * $i);
                    if(strtolower(date('l', $time)) == strtolower($reminder['reminder_day'])){
                        $next_time = date('Y-m-d', $time).' '.$reminder['reminder_time'];
                    }
                    $i++;
                }
                UserGoalReminder::create([
                    'user_id' => $this->user->id,
                    'goal_category_id' => $category->id,
                    'reminder' => $reminder['reminder'],
                    'reminder_day' => $reminder['reminder_day'],
                    'reminder_time' => $reminder['reminder_time'],
                    'reminder_type' => $reminder_type,
                    'next_reminder' => $next_time
                ]);
            }
        }

        $next_answer->goal_set = 1;
        $next_answer->save();

        $user = User::find($this->user->id);
        $user->goals_completed += 1;
        $user->save();

        return response([
            'status' => 'success',
            'message' => 'Goal set successfully',
            'data' => $saved_answers
        ], 200);
    }

    public function previous_reflections(){
        $slug = !empty($_GET['slug']) ? (string)$_GET['slug'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $answers = GoalReflectionAnswer::where('user_id', $this->user->id);
        if(!empty($slug)){
            $category = GoalCategory::whre('slug', $slug)->first();
            if(!empty($category)){
                $answers = $answers->where('goal_category_id', $category->id);
            }
        }
        $answers = $answers->orderBy('created_at', 'desc');

        $answers = $answers->paginate($limit);
        if(!empty($answers)){
            foreach($answers as $answer){
                $answer->answers = json_decode($answer->answers);
                $answer->goal_category = GoalCategory::find($answer->goal_category_id)->category;
            }
        }
        
        return response([
            'status' => 'success',
            'message' => 'Previous Reflections fetched successfully',
            'data' => $answers
        ], 200);
    }

    public function previous_goals(){
        $slug = !empty($_GET['slug']) ? (string)$_GET['slug'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $answers = UserGoalAnswer::where('user_id', $this->user->id);
        if(!empty($slug)){
            $category = GoalCategory::whre('slug', $slug)->first();
            if(!empty($category)){
                $answers = $answers->where('goal_category_id', $category->id);
            }
        }
        $answers = $answers->orderBy('created_at', 'desc');

        $answers = $answers->paginate($limit);
        if(!empty($answers)){
            foreach($answers as $answer){
                $answer->answers = json_decode($answer->answers);
                $answer->goal_category = GoalCategory::find($answer->goal_category_id)->category;
            }
        }
        
        return response([
            'status' => 'success',
            'message' => 'Previous Reflections fetched successfully',
            'data' => $answers
        ], 200);
    }

    public function all_reminders(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 0;
        
        $reminders = UserGoalReminder::where('user_id', $this->user->id)->where('status', 1);
        if($reminders->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'You do not have any active reminder at this time',
                'data' => []
            ], 200);
        }
        
        $reminders = $reminders->paginate($limit);
        foreach($reminders as $reminder){
            $reminder->category = GoalCategory::find($reminder->goal_category_id)->category;
        }

        return response([
            'status' => 'success',
            'message' => 'Goal Category fetched successfully',
            'data' => $reminders
        ], 200);
    }
}
