<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerDassQuestionRequest;
use App\Models\Category;
use App\Models\CurrentSubscription;
use App\Models\DailyQuestionAnswer;
use App\Models\DassQuestion;
use App\Models\DassQuestionOption;
use App\Models\PremiumCategoryScoreRange;
use App\Models\QuestionAnswerSummary;
use App\Models\SubscriptionPackage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DassQuestionController extends Controller
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

    private function check_validity(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', $this->time->format('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return false;
        }

        $package = SubscriptionPackage::find($current_subscription->subscription_package_id);
        if(empty($package) or ($package->free_trial == 1)){
            return false;
        }

        return true;
    }

    public function fetch_questions(){
        if(!$this->check_validity()){
            return response([
                'status' => 'failed',
                'message' => 'Not Authorised'
            ], 409);
        }
        $last_answer = QuestionAnswerSummary::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        $fetch = false;
        if($last_answer->count() < 1){
            $fetch = true;
        } else {
            $last_answer = $last_answer->first();
            $today = $this->time->format('Y-m-d');

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

        $questions = DassQuestion::orderBy('created_at', 'asc');
        if($questions->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Question was fetched'
            ], 404);
        }

        $questions = $questions->get(['id', 'question']);
        foreach($questions as $question){
            $question->option = DassQuestionOption::orderBy('value', 'asc')->get();
        }

        return response([
            'status' => 'success',
            'message' => 'Questions fetched successfully',
            'data' => $questions
        ], 200);
    }

    public function answer_dass_questions(AnswerDassQuestionRequest $request){
        if(!$this->check_validity()){
            return response([
                'status' => 'failed',
                'message' => 'Not Authorised'
            ], 409);
        }
        $last_answer = QuestionAnswerSummary::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        $fetch = false;
        if($last_answer->count() < 1){
            $fetch = true;
        } else {
            $last_answer = $last_answer->first();
            $today = $this->time->format('Y-m-d');

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

        self::delete_temp_answer($this->user->id);

        $total_score = 0;
        $k10_score = 0;
        $category_scores = [];
        $category_total = [];
        $premium_scores = [];
        $answers = [];
        
        $highest_value = DassQuestionOption::orderBy('value', 'desc')->first()->value;
        foreach($request->answers as $answer){
            $question = DassQuestion::find($answer['question_id']);
            $option = DassQuestionOption::find($answer['option_id']);

            $ans = [
                'question_id' => $question->id,
                'question' => $question
            ];

            $total_score += $option->value;

            $categories = explode(',', $question->categories);
            foreach($categories as $id){
                $category = Category::find(trim($id));
                if(!empty($category)){
                    if(isset($category_scores[$category->id])){
                        $category_scores[$category->id] += $option->value;
                    } else {
                        $category_scores[$category->id] = $option->value;
                    }
                    if(!isset($category_total[$category->id])){
                        $category_total[$category->id] = 0;
                    }
                    $category_total[$category->id] += $highest_value;

                    if($category->premium_category == 1){
                        if(isset($premium_scores[$category->id])){
                            $premium_scores[$category->id] += $option->value;
                        } else {
                            $premium_scores[$category->id] = $option->value;
                        }
                    }
                }
            }

            $ans['answer'] = $option->option;
            $ans['value'] = $option->value;

            $answers[] = $ans;
        }

        $categ_scores = [];
        $prem_scores = [];
        $percent_list = [];
        $daily_percent_list = [];

        foreach($category_scores as $key=>$value){
            $category = Category::find($key);
            $percentage = ($value / $category_total[$key]) * 100;
            $categ_scores[] = [
                'category_id' => $category->id,
                'category' => $category->category,
                'value' => $value,
                'percentage' => $percentage
            ];
            $percent_list[$key] = $percentage;
        }

        foreach($premium_scores as $key=>$value){
            $category = Category::find($key);            
            $verdict = PremiumCategoryScoreRange::where('category_id', $category->id)->where('min', '<=', $value)->where('max', '>=', $value)->first()->verdict;
            $prem_scores[] = [
                'category_id' => $category->id,
                'category' => $category->category,
                'value' => $value,
                'verdict' => $verdict
            ];
        }

        $uncomputeds = DailyQuestionAnswer::where('user_id', $this->user->id)->where('computed', 0);
        if($uncomputeds->count() > 0){
            $daily_score = [];
            $daily_total = [];
            foreach($uncomputeds->get() as $uncomputed){
                $scores = json_decode($uncomputed->category_scores, true);
                foreach($scores as $score){
                    // $category_scores[$score['category_id']] += $score['value'];
                    if(!isset($daily_score[$score['category_id']])){
                        $daily_score[$score['category_id']] = 0;
                    }
                    $daily_score[$score['category_id']] += $score['value'];

                    if(!isset($daily_total[$score['category_id']])){
                        $daily_total[$score['category_id']] = 0;
                    }
                    $daily_total[$score['category_id']] += $score['highest_value'];
                }
                $uncomputed->computed = 1;
                $uncomputed->save();
            }

            foreach($daily_score as $key=>$value){
                $daily_percent_list[$key] = ($daily_score[$key]/$daily_total[$key]) * 100;
            }
        }

        foreach($percent_list as $key=>$value){
            if(isset($daily_percent_list[$key])){
                $percent_list[$key] = ($value + $daily_percent_list[$key]) / 2;
            }
        }

        arsort($percent_list);
        $cat_list = array_keys($percent_list);

        $highest_cat_id = array_shift($cat_list);
        $highest_cat = Category::find($highest_cat_id)->category;

        $second_highest_cat_id = array_shift($cat_list);
        $second_highest_category = Category::find($second_highest_cat_id)->category;

        $next_question = $this->time->addDays(7)->format('Y-m-d');
        // $next_question = date('Y-m-d');

        $answer_summary = QuestionAnswerSummary::create([
            'user_id' => $this->user->id,
            'question_type' => 'dass_question',
            'answers' => json_encode($answers),
            'k10_scores' => $k10_score,
            'total_score' => $total_score,
            'distress_level' => '',
            'premium_scores' => json_encode($prem_scores),
            'category_scores' => json_encode($categ_scores),
            'highest_category_id' => $highest_cat_id,
            'highest_category' => $highest_cat,
            'second_highest_category_id' => $second_highest_cat_id,
            'second_highest_category' => $second_highest_category,
            'next_question' => $next_question
        ]);

        $answer_summary->answers = $answers;
        $answer_summary->premium_scores = $prem_scores;
        $answer_summary->category_scores = $categ_scores;

        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', $this->time->format('Y-m-d'))->where('status', 1)->orderBy('grace_end', 'asc')->first();
        if(!empty($current_subscription)){
            $package = SubscriptionPackage::find($current_subscription->subscription_package_id);
        } else {
            $package = SubscriptionPackage::where('free_package', 1)->first();
        }

        $book_limit = ($package->book_limit >= 0) ? $package->book_limit : 1000000000;
        $podcast_limit = ($package->podcast_limit >= 0) ? $package->podcast_limit : 1000000000;
        $article_limit = ($package->article_limit >= 0) ? $package->article_limit : 1000000000;
        $audio_limit = ($package->audio_limit >= 0) ? $package->audio_limit : 1000000000;
        $video_limit = ($package->video_limit >= 0) ? $package->video_limit : 1000000000;
        $listen_and_learn_limit = ($package->listen_and_learn_limit >= 0) ? $package->listen_and_learn_limit : 1000000000;
        $read_and_reflect_limit = ($package->read_and_reflect_limit >= 0) ? $package->read_and_reflect_limit : 1000000000;
        $learn_and_do_limit = ($package->learn_and_do_limit >= 0) ? $package->learn_and_do_limit : 1000000000;

        BookController::recommend_books($book_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);
        PodcastController::recommend_podcasts($podcast_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);
        ArticleController::recommend_articles($article_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);
        AudioController::recommend_audios($audio_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);
        VideoController::recommend_videos($video_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);
        ListenAndLearnController::recommend_strategies($listen_and_learn_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);
        ReadAndReflectController::recommend_strategies($read_and_reflect_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);
        LearnAndDoController::recommend_strategies($learn_and_do_limit, $this->user->id, $highest_cat_id, $second_highest_cat_id, $package->level);

        $user = User::find($this->user->id);
        $user->next_assessment = $next_question;
        $user->save();
        
        self::log_activity($this->user->id, "answered_dass21_question", "question_answer_summaries", $answer_summary->id);

        return response([
            'status' => 'success',
            'message' => 'Dass Questions answered',
            'data' => $answer_summary
        ], 200);
    }

    public function distress_scores(){
        if(!$this->check_validity()){
            return response([
                'status' => 'failed',
                'message' => 'Not Authorised'
            ], 409);
        }

        $prem_scores = QuestionAnswerSummary::where('user_id', $this->user->id)->where('question_type', 'dass_question');
        if($prem_scores->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'You are yet to complete any Dass21 Question'
            ], 404);
        }

        $prem_scores = $prem_scores->first()->premium_scores;
        $prem_scores = json_decode($prem_scores, true);
        $scores = [];
        foreach($prem_scores as $prem_score){
            $scores[] = [
                'category' => $prem_score['category'],
                'score' => $prem_score['value'],
                'verdict' => $prem_score['verdict']
            ];
        }

        return response([
            'status' => 'success',
            'message' => 'Latest Distress Score fetched successfully',
            'data' => $scores
        ], 200);
    }

    public function answer_temp(AnswerDassQuestionRequest $request){
        if(!$this->check_validity()){
            return response([
                'status' => 'failed',
                'message' => 'Not Authorised'
            ], 409);
        }
        $last_answer = QuestionAnswerSummary::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        $fetch = false;
        if($last_answer->count() < 1){
            $fetch = true;
        } else {
            $last_answer = $last_answer->first();
            $today = $this->time->format('Y-m-d');

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
        
        self::temp_answer($this->user->id, 'Dass21 Questions', $request->answers);
        return response([
            'status' => 'success',
            'message' => 'Answers saved successfully'
        ]);
    }

    public function fetch_dass_temp_answer(){
        return response([
            'status' => 'success',
            'message' => 'Answers fetched successfully',
            'data' => self::fetch_temp_answer_by_type($this->user->id, 'Dass21 Questions')
        ], 200);
    }
}
