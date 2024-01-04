<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Models\Interest;
use App\Models\BasicQuestion;
use App\Models\DistressScoreRange;
use App\Models\BasicQuestionOption;
use App\Models\CurrentSubscription;
use App\Models\DailyQuestionAnswer;
use App\Models\SubscriptionPackage;
use App\Models\PrerequisiteQuestion;
use App\Models\QuestionAnswerSummary;
use App\Http\Requests\SetInterestRequest;
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
        $last_answer = QuestionAnswerSummary::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
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

        self::delete_temp_answer($this->user->id);

        $total_score = 0;
        $k10_score = 0;
        $category_scores = [];
        $category_total = [];
        $premium_scores = [];
        $answers = [];

        foreach($request->answers as $answer){
            $question = BasicQuestion::find($answer['question_id']);
            if($question->special_options == 1){
                $option = BasicQuestionSpecialOption::find($answer['option_id']);
                $highest_value = BasicQuestionSpecialOption::orderBy('value', 'desc')->first()->value;
            } else {
                $option = BasicQuestionOption::find($answer['option_id']);
                $highest_value = BasicQuestionOption::orderBy('value', 'desc')->first()->value;
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

        $distress_level = DistressScoreRange::where('question_type', 'basic_question')->where('min', '<=', $k10_score)->where('max', '>=', $k10_score)->first()->verdict;

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

        $next_question = date('Y-m-d', time() + (60 * 60 * 24 * 14));
        $current = CurrentSubscription::where('user_id', $this->user->id)->where('end_date', '>', date('Y-m-d'))->first();
        if(!empty($current)){
            $package = SubscriptionPackage::find($current->subscription_package_id);
            if($package->free_trial == 1){
                if(((strtolower($package->duration_type) == 'week') and ($package->duration < 2)) or ((strtolower($package->durtion_type) == 'day') and ($package->duration < 14))){
                    $next_question = date('Y-m-d', strtotime($current->end_date.' 01:00:00') + (60 * 60 * 24));                    
                }
            }
        }

        // $next_question = date('Y-m-d');

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
            'second_highest_category_id' => $second_highest_cat_id,
            'second_highest_category' => $second_highest_category,
            'next_question' => $next_question
        ]);

        $answer_summary->answers = $answers;
        $answer_summary->premium_scores = $prem_scores;
        $answer_summary->category_scores = $categ_scores;
        $answer_summary->welcome_message = "";
        
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

        self::log_activity($this->user->id, "answered_basic_question", "question_answer_summaries", $answer_summary->id);

        if($new){
            $gpt = new ChatGPTController();
            $name = explode(' ', $this->user->name);
            $name = $name[0];

            $welcome_message = $gpt->welcome_message($name, $distress_level);
            $answer_summary->welcome_message = $welcome_message;
        }

        return response([
            'status' => 'success',
            'message' => 'Basic Question answered',
            'data' => $answer_summary
        ], 200);
    }

    public function fetch_interests(){
        $interests = Interest::orderBy('interest', 'asc')->get(['interest']);

        return response([
            'status' => 'success',
            'message' => 'Interests fetched successfully',
            'data' => $interests
        ], 200);
    }

    public function set_interests(SetInterestRequest $request){
        $user = User::find($this->user->id);
        if(!empty($user->interests)){
            return response([
                'status' => 'failed',
                'messaage' => 'User Interest already set'
            ], 409);
        }
        
        $user->interests = join(',', $request->interests);
        $user->save();
        
        foreach($request->interests as $interest){
            $int = Interest::where('interest', $interest)->first();
            $int->total_users += 1;
            $int->save();
        }

        return response([
            'status' => 'success',
            'message' => 'Interest set successfully'
        ], 200);
    }

    public function answer_temp(AnswerBasicQuestionRequest $request){
        $last_answer = QuestionAnswerSummary::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
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
        
        self::temp_answer($this->user->id, 'Basic Questions', $request->answers);
        return response([
            'status' => 'success',
            'message' => 'Answers saved successfully'
        ]);
    }

    public function fetch_basic_temp_answer(){
        return response([
            'status' => 'success',
            'message' => 'Answers fetched successfully',
            'data' => self::fetch_temp_answer_by_type($this->user->id, 'Basic Questions')
        ], 200);
    }
}
