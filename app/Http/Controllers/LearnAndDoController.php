<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LearnAndDo;
use Illuminate\Http\Request;
use App\Models\LearnAndDoAnswer;
use App\Models\OpenedLearnAndDo;
use App\Models\FavouriteResource;
use App\Models\LearnAndDoActivity;
use App\Models\LearnAndDoQuestion;
use App\Models\CurrentSubscription;
use App\Models\RecommendedLearnAndDo;
use App\Http\Requests\AnswerLearnAndDoQuestionRequest;

class LearnAndDoController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api', ['except' => ['recommend_strategies']]);
        $this->user = AuthController::user();
    }

    public static function recommend_strategies($limit, $user_id, $cat_id, $sec_cat_id, $level=0){
        $rec_strategies = RecommendedLearnAndDo::where('user_id', $user_id);
        if($rec_strategies->count() > 0){
            foreach($rec_strategies->get() as $rec_strategy){
                $rec_strategy->delete();
            }
        }

        if($limit > 0){
            $opened_strategies = [];
            $op_strategies = OpenedLearnAndDo::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_strategies->count() > 0){
                foreach($op_strategies as $op_strategy){
                    $opened_strategies[] = $op_strategy->listen_and_learn_id;
                }
            }

            $strategies_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $learns = LearnAndDo::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($learns as $learn){
                if(count($strategies_id) < $first_limit){
                    $categories = explode(',', $learn->categories);
                    if(in_array($cat_id, $categories) and !in_array($learn->id, $opened_strategies)){
                        $strategies_id[] = $learn;
                        $ids[] = $learn->id;
                    }
                } else {
                    break;
                }
            }

            if(count($strategies_id) < $first_limit){
                foreach($opened_strategies as $opened_strategy){
                    if(count($strategies_id) < $first_limit){
                        $learn = LearnAndDo::find($opened_strategy);
                        if(!empty($learn) and ($learn->status == 1) and ($learn->subscription_level <= $level)){
                            $categories = explode(',', $learn->categories);
                            if(in_array($cat_id, $categories)){
                                $strategies_id[] = $learn;
                                $ids[] = $learn->id;
                            }                            
                        }
                    } else {
                        break;
                    }
                }
            }

            $counting = count($strategies_id);
            if(($counting < $limit) and (LearnAndDo::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counting)){
                $s_learns = LearnAndDo::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($strategies_id)){
                    foreach($strategies_id as $strategy_id){
                        $s_learns = $s_learns->where('id', '<>', $strategy_id->id);
                    }
                }
                $s_learns = $s_learns->orderBy('created_at', 'desc')->get(['id', 'slug', 'categories']);
                foreach($s_learns as $learn){
                    if(count($strategies_id) < $limit){
                        $categories = explode(',', $learn->categories);
                        if(in_array($sec_cat_id, $categories) and !in_array($learn->id, $opened_strategies)){
                            $strategies_id[] = $learn;
                            $ids[] = $learn->id;
                        }
                    } else {
                        break;
                    }
                }

                if(count($strategies_id) < $limit){
                    foreach($opened_strategies as $opened_strategy){
                        if(count($strategies_id) < $limit){
                            $learn = LearnAndDo::find($opened_strategy);
                            if(!empty($learn) and ($learn->status == 1) and ($learn->subscription_level <= $level)){
                                $categories = explode(',', $learn->categories);
                                if(in_array($sec_cat_id, $categories)){
                                    $strategies_id[] = $learn;
                                    $ids[] = $learn->id;
                                }
                            }
                        } else {
                            break;
                        }
                    }
                }
            }

            $counted = count($strategies_id);
            if(($counted < $limit) and (LearnAndDo::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
                $other_learns = LearnAndDo::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($strategies_id)){
                    foreach($strategies_id as $strategy_id){
                        $other_learns = $other_learns->where('id', '<>', $strategy_id->id);
                    }
                }
                $other_learns = $other_learns->inRandomOrder();
                if($other_learns->count() > 0){
                    $other_learns = $other_learns->get(['id', 'slug']);
                    foreach($other_learns as $other_learn){
                        if(count($strategies_id) < $limit){
                            if(!in_array($other_learn->id, $opened_strategies)){
                                $strategies_id[] = $other_learn;
                                $ids[] = $other_learn->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($strategies_id) < $limit){
                        foreach($other_learns as $other_learn){
                            if(count($strategies_id) < $limit){
                                if(!in_array($other_learn->id, $ids)){
                                    $strategies_id[] = $other_learn;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            if(!empty($strategies_id)){
                foreach($strategies_id as $strategy){
                    RecommendedLearnAndDo::create([
                        'user_id' => $user_id,
                        'learn_and_do_id' => $strategy->id,
                        'slug' => $strategy->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }

    public function fetch_strategy(LearnAndDo $learn, $user_id) : LearnAndDo
    {
        if(!empty($learn->photo)){
            $learn->photo = FileManagerController::fetch_file($learn->photo);
        }

        if(!empty($learn->categories)){
            $categories = [];

            $categs = explode(',', $learn->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $learn->categories = $categories;
        }
        $activities = LearnAndDoActivity::where('learn_and_do_id', $learn->id);
        if($activities->count() > 0){
            $activities = $activities->get();
            foreach($activities as $activity){
                $activity = $this->fetch_activity($activity);
            }
            $learn->activities = $activities;
        } else {
            $learn->activities = [];
        }
        $learn->favourited = !empty(FavouriteResource::where('resource_id', $learn->id)->where('user_id', $user_id)->where('type', 'learn_and_do')->first()) ? true : false;

        unset($learn->id);
        unset($learn->created_at);
        unset($learn->updated_at);
        return $learn;
    }

    public function fetch_activity(LearnAndDoActivity $activity) : LearnAndDoActivity
    {
        $activity->questions = LearnAndDoQuestion::where('learn_and_do_id', $activity->learn_and_do_id)->where('activity_id', $activity->id)->get();
        return $activity;
    }

    public function recommended_strategies(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_learns = RecommendedLearnAndDo::where('user_id', $this->user->id);
        if($rec_learns->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Recommended Strategy',
                'data' => $rec_learns->paginate($limit)
            ], 200);
        }

        $learns = [];
        $rec_learns = $rec_learns->get();
        if(empty($search)){
            foreach($rec_learns as $rec_learn){
                $learn = LearnAndDo::find($rec_learn->learn_and_do_id);
                if(!empty($learn) and ($learn->status == 1)){
                    $learns[] = $this->fetch_strategy($learn, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_learns as $rec_learn){
                $learn = LearnAndDo::find($rec_learn->learn_and_do_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($learn->title, $word) !== FALSE) or (strpos($learn->overview, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$learn->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $learn = LearnAndDo::find($key);
                    if(!empty($learn) and ($learn->status == 1)){
                        $learns[] = $this->fetch_strategy($learn, $this->user->id);
                    }
                }
            }
        }

        if(empty($learns)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was found',
                'data' => self::paginate_array($learns, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Strategies fetched successfully',
            'data' => self::paginate_array($learns, $limit, $page)
        ], 200);
    }

    public function recommended_strategy($slug){
        $rec_learn = RecommendedLearnAndDo::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_learn)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $learn = LearnAndDo::find($rec_learn->learn_and_do_id);
        if(empty($learn) or ($learn->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Strategy fetched successfully',
            'data' => $this->fetch_strategy($learn, $this->user->id)
        ], 200);
    }

    public function mark_as_opened($slug){
        $learn = LearnAndDo::where('slug', $slug)->first();
        if(empty($learn)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $opened = OpenedLearnAndDo::where('user_id', $this->user->id)->where('learn_and_do_id', $learn->id)->first();
        if(empty($opened)){
            OpenedLearnAndDo::create([
                'user_id' => $this->user->id,
                'learn_and_do_id' => $learn->id,
                'frequency' => 1
            ]);
            self::complete_resource($this->user->id);
        } else {
            $opened->frequency += 1;
            $opened->save();
        }

        $learn->opened_count += 1;
        $learn->save();

        $learn = $this->fetch_strategy($learn, $this->user->id);
        if(!empty($learn->categories)){
            self::category_log($this->user->id, $learn->categories);
        }

        return response([
            'status' => 'success',
            'message' => 'Marked as Opened'
        ], 200);
    }

    public function opened_strategies(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $opened_strategies = OpenedLearnAndDo::where('user_id', $this->user->id);
        if($opened_strategies->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy',
                'data' => $opened_strategies->paginate($limit)
            ], 200);
        }

        $learns = [];
        $opened_learns = $opened_strategies->get();
        if(empty($search)){
            foreach($opened_learns as $opened_learn){
                $learn = LearnAndDo::find($opened_learn->learn_and_do_id);
                if(!empty($learn) and ($learn->status == 1)){
                    $learns[] = $this->fetch_strategy($learn, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_learns as $opened_learn){
                $learn = LearnAndDo::find($opened_learn->learn_and_do_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($learn->title, $word) !== FALSE) or (strpos($learn->overview, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$learn->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $learn = LearnAndDo::find($key);
                    if(!empty($learn) and ($learn->status == 1)){
                        $learns[] = $this->fetch_strategy($learn, $this->user->id);
                    }
                }
            }
        }

        if(empty($learns)){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy was found',
                'data' => self::paginate_array($learns, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Opened Listen and Learn Strategies fetched successfully',
            'data' => self::paginate_array($learns, $limit, $page)
        ], 200);
    }

    public function opened_strategy($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $learn = LearnAndDo::where('slug', $slug)->first();
        if(empty($learn) or ($learn->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $opened = OpenedLearnAndDo::where('learn_and_do_id', $learn->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Learn and Do Strategy fetched successfully',
            'data' => $this->fetch_strategy($learn, $this->user->id)
        ], 200);
    }

    public function strategy_favourite($slug){
        $learn = LearnAndDo::where('slug', $slug)->first();
        if(empty($learn)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $action = self::favourite_resource('learn_and_do', $this->user->id, $learn->id);
        if($action == 'saved'){
            $learn->favourite_count += 1;
        } else {
            $learn->favourite_count -= 1;
        }
        $learn->save();
        $learn->update_dependencies();
        
        $message = ($action == 'saved') ? 'Strategy added to Favourites' : 'Strategy removed from Favourites';

        return response([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function favourite_strategies(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $fav_learns = FavouriteResource::where('type', 'learn_and_do')->where('user_id', $this->user->id);
        if($fav_learns->count() < 1){
            return response([
                'staatus' => 'failed',
                'message' => 'No Favourite Strategy was fetched',
                'data' => $fav_learns->paginate($limit)
            ], 200);
        }

        $learns = [];
        $fav_learns = $fav_learns->get();
        if(empty($search)){
            foreach($fav_learns as $fav_learn){
                $learn = LearnAndDo::find($fav_learn->resource_id);
                if(!empty($learn) and ($learn->status == 1)){
                    $learns[] = $this->fetch_strategy($learn, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_learns as $fav_learn){
                $learn = LearnAndDo::find($fav_learn->resource_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($learn->title, $word) !== FALSE) or (strpos($learn->overview, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$learn->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $learn = LearnAndDo::find($key);
                    if(!empty($learn) and ($learn->status == 1)){
                        $learns[] = $this->fetch_strategy($learn, $this->user->id);
                    }
                }
            }
        }

        if(empty($learns)){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Strategy was found',
                'data' => self::paginate_array($learns, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Favourite Learn and Do Strategies fetched successfully',
            'data' => self::paginate_array($learns, $limit, $page)
        ], 200);
    }

    public function favourite_strategy($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $learn = LearnAndDo::where('slug', $slug)->first();
        if(empty($learn) or ($learn->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $fav_learn = FavouriteResource::where('resource_id', $learn->id)->where('user_id', $this->user->id)->where('type', 'learn_and_do')->first();
        if(empty($fav_learn)){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Strategy was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Listen and Learn Strategy fetched successfully',
            'data' => $this->fetch_strategy($learn, $this->user->id)
        ], 200);
    }

    public function answer_questions(AnswerLearnAndDoQuestionRequest $request, $slug){
        $learn = LearnAndDo::where('slug', $slug)->first();
        if(empty($learn)){
            return response([
                'status' => 'failed',
                'message' => 'Learn and Do Strategy not found'
            ], 404);
        }

        $answers = [];
        foreach($request->answers as $answer){
            $question = LearnAndDoQuestion::find($answer['question_id']);
            if(!empty($question) and ($question->learn_and_do_id == $learn->id)){
                $answers[] = [
                    'question_id' => $answer['question_id'],
                    'question' => $question->question,
                    'answer' => $answer['answer']
                ];
            }
        }

        $answered = LearnAndDoAnswer::create([
            'user_id' => $this->user->id,
            'learn_and_do_id' => $learn->id,
            'answers' => json_encode($answers)
        ], 200);

        $answered->answers = json_decode($answered->answers, true);

        return response([
            'status' => 'success',
            'message' => 'Questions answered successful',
            'data' => $answered
        ], 200);
    }

    public function previous_answers($slug){
        $learn = LearnAndDo::where('slug', $slug)->first();
        if(empty($learn)){
            return response([
                'status' => 'failed',
                'message' => 'Learn and Do Strategy not found'
            ], 404);
        }

        $answers = LearnAndDoAnswer::where('learn_and_do_id', $learn->id)->where('user_id', $this->user->id)->orderBy('created_at', 'desc');
        if($answers->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Answer has been provided yet',
                'data' => null
            ], 200);
        }

        $answers = $answers->paginate(10);
        foreach($answers as $answer){
            $answer->answers = json_decode($answer->answers, true);
        }

        return response([
            'status' => 'success',
            'message' => 'Answers fetched successfully',
            'data' => $answers
        ], 200);
    }
}
