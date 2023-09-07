<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Article;
use App\Models\Podcast;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\BasicQuestion;
use App\Models\OpenedResources;
use App\Models\RecommendedBook;
use App\Models\DistressScoreRange;
use App\Models\RecommendedArticle;
use App\Models\RecommendedPodcast;
use App\Models\BasicQuestionOption;
use App\Models\CurrentSubscription;
use App\Models\DailyQuestionAnswer;
use App\Models\SubscriptionPackage;
use App\Models\PrerequisiteQuestion;
use App\Models\QuestionAnswerSummary;
use App\Models\PremiumCategoryScoreRange;
use App\Models\BasicQuestionSpecialOption;
use App\Http\Requests\AnswerBasicQuestionRequest;

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

    public function answer_basic_question(AnswerBasicQuestionRequest $request){
        $last_answer = QuestionAnswerSummary::where('user_id', $this->user->id);
        $fetch = false;
        if($last_answer->count() < 1){
            $fetch = true;
            $new = true;
        } else {
            $last_answer = $last_answer->first();
            $today = date('Y-m-d');

            if($today >= $last_answer->next_question){
                $fetch = true;
            }
            $new = false;
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
            $question = BasicQuestion::find($answer['question_id']);
            if($question->special_options == 1){
                $option = BasicQuestionSpecialOption::find($answer['option_id']);
            } else {
                $option = BasicQuestionOption::find($answer['option_id']);
            }

            $ans = [
                'question_id' => $question->id,
                'question' => $question->question
            ];

            if($question->has_prerequisiste){
                $prerequisite = PrerequisiteQuestion::where('basic_question_id', $question->id)->first();
                
                foreach($request->answers as $t_answer){
                    if($t_answer['question_id'] == $prerequisite->prerequisite_id){
                        $t_question = BasicQuestion::find($t_answer['question_id']);
                        if($t_question->special_options == 1){
                            $t_option = BasicQuestionSpecialOption::find($t_answer['option_id']);
                        } else {
                            $t_option = BasicQuestionOption::find($t_answer['option_id']);
                        }
                        if($t_option->prerequisite_value == $t_option->value){
                            if($prerequisite->action == 'skip'){
                                if($question->special_options == 1){
                                    $option = BasicQuestionSpecialOption::where('basic_question_id', $question->id)->where('value', $prerequisite->default_value)->first();
                                } else {
                                    $option = BasicQuestionOption::where('value', $prerequisite->default_value)->first();
                                }
                            }
                        }
                        break;
                    }
                }
            }

            $total_score += $option->value;
            if($question->is_k10){
                $k10_score += $option->value;
            }

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

        $distress_level = DistressScoreRange::where('question_type', 'basic_question')->where('min', '<=', $k10_score)->where('max', '>=', $k10_score)->first()->verdict;

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

        $next_question = date('Y-m-d', time() + (60 * 60 * 24 * 14));

        $answer_summary = QuestionAnswerSummary::create([
            'user_id' => $this->user->id,
            'question_type' => 'basic_question',
            'answers' => json_encode($answers),
            'k10_scores' => $k10_score,
            'total_score' => $total_score,
            'distress_level' => $distress_level,
            'premium_scores' => json_encode($prem_scores),
            'category_scores' => json_encode($categ_scores),
            'highest_category_id' => $highest_cat_id,
            'highest_category' => $highest_cat,
            'next_question' => $next_question
        ]);

        $answer_summary->answers = $answers;
        $answer_summary->premium_scores = $prem_scores;
        $answer_summary->category_scores = $categ_scores;
        $answer_summary->highest_category_id = $highest_category_id;
        $answer_summary->highest_category = $highest_category;

        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('end_date', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(!empty($current_subscription)){
            $package = SubscriptionPackage::find($current_subscription->subscription_package_id);
        } else {
            $package = SubscriptionPackage::where('free_package', 1)->first();
        }

        BookController::recommend_books($package->book_limit, $this->user->id, $highest_cat_id);
        PodcastController::recommend_podcasts($package->podcast_limit, $this->user->id, $highest_cat_id);
        ArticleController::recommend_articles($package->audio_limit, $this->user->id, $highest_cat_id);
        AudioController::recommend_audios($package->audio_limit, $this->user->id, $highest_cat_id);
        VideoController::recommend_videos($package->video_limit, $this->user->id, $highest_cat_id);

        self::log_activity($this->user->id, "answered_basic_question", "question_answer_summaries", $answer_summary->id);

        return response([
            'status' => 'success',
            'message' => 'Basic Question answered',
            'data' => $answer_summary
        ], 200);
    }
}