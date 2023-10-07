<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerDassQuestionRequest;
use App\Models\Category;
use App\Models\CurrentSubscription;
use App\Models\DailyQuestionAnswer;
use App\Models\DassQuestion;
use App\Models\DassQuestionOption;
use App\Models\PremiumCategoryScoreRange;
use Illuminate\Http\Request;
use App\Models\QuestionAnswerSummary;
use App\Models\SubscriptionPackage;

class DassQuestionController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    private function check_validity(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
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

        $total_score = 0;
        $k10_score = 0;
        $category_scores = [];
        $premium_scores = [];
        $answers = [];

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
        $score_list = [];
        $highest_category_id = [];
        $highest_category = [];

        foreach($category_scores as $key=>$value){
            $category = Category::find($key);
            $categ_scores[] = [
                'category_id' => $category->id,
                'category' => $category->category,
                'value' => $value
            ];
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
            foreach($uncomputeds->get() as $uncomputed){
                $scores = json_decode($uncomputed->category_scores, true);
                foreach($scores as $score){
                    $category_scores[$score['category_id']] += $score['value'];
                }
                $uncomputed->computed = 1;
                $uncomputed->save();
            }
        }

        foreach($category_scores as $key=>$value){
            if(!isset($score_list[$value])){
                $score_list[$value] = [];
            }
            $score_list[$value][] = $key;
        }

        krsort($score_list);

        $highest = array_shift($score_list);

        foreach($highest as $high){
            $highest_category_id[] = $high;
            $category = Category::find($high);
            $highest_category[] = $category->category;
        }

        $highest_cat_id = array_shift($highest_category_id);
        $highest_cat = array_shift($highest_category);

        // $next_question = date('Y-m-d', time() + (60 * 60 * 24 * 7));
        $next_question = date('Y-m-d');

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
            'next_question' => $next_question
        ]);

        $answer_summary->answers = $answers;
        $answer_summary->premium_scores = $prem_scores;
        $answer_summary->category_scores = $categ_scores;

        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->orderBy('grace_end', 'asc')->first();
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

        BookController::recommend_books($book_limit, $this->user->id, $highest_cat_id, $package->level);
        PodcastController::recommend_podcasts($podcast_limit, $this->user->id, $highest_cat_id, $package->level);
        ArticleController::recommend_articles($article_limit, $this->user->id, $highest_cat_id, $package->level);
        AudioController::recommend_audios($audio_limit, $this->user->id, $highest_cat_id, $package->level);
        VideoController::recommend_videos($video_limit, $this->user->id, $highest_cat_id, $package->level);

        self::log_activity($this->user->id, "answered_dass21_question", "question_answer_summaries", $answer_summary->id);

        return response([
            'status' => 'success',
            'message' => 'Basic Question answered',
            'data' => $answer_summary
        ], 200);
    }
}
