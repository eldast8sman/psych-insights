<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ListenAndLearn;
use App\Models\FavouriteResource;
use App\Models\CurrentSubscription;
use App\Models\ListenAndLearnAudio;
use App\Models\SubscriptionPackage;
use App\Models\OpenedListenAndLearn;
use App\Models\RecommendedListenAndLearn;

class ListenAndLearnController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api', ['except' => ['recommend_strategies']]);
        $this->user = AuthController::user();
    }

    public static function recommend_strategies($limit, $user_id, $cat_id, $sec_cat_id, $level=0){
        $rec_strategies = RecommendedListenAndLearn::where('user_id', $user_id);
        if($rec_strategies->count() > 0){
            foreach($rec_strategies->get() as $rec_strategy){
                $rec_strategy->delete();
            }
        }

        if($limit > 0){
            $opened_strategies = [];
            $op_strategies = OpenedListenAndLearn::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_strategies->count() > 0){
                foreach($op_strategies as $op_strategy){
                    $opened_strategies[] = $op_strategy->listen_and_learn_id;
                }
            }

            $strategies_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $learns = ListenAndLearn::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
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
                        $learn = ListenAndLearn::find($opened_strategy);
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
            if(($counting < $limit) and (ListenAndLearn::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counting)){
                $s_learns = ListenAndLearn::where('status', 1)->where('subscription_level', '<=', $level);
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
                            $learn = ListenAndLearn::find($opened_strategy);
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
            if(($counted < $limit) and (ListenAndLearn::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
                $other_learns = ListenAndLearn::where('status', 1)->where('subscription_level', '<=', $level);
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
                    RecommendedListenAndLearn::create([
                        'user_id' => $user_id,
                        'listen_and_learn_id' => $strategy->id,
                        'slug' => $strategy->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }

    public function fetch_strategy(ListenAndLearn $learn, $user_id) : ListenAndLearn
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

        $learn_audios = [];
        $audios = ListenAndLearnAudio::where('listen_and_learn_id', $learn->id)->get();
        if(!empty($audios)){
            foreach($audios as $audio){
                $learn_audios[] = FileManagerController::fetch_file($audio->audio);
            }
        }
        $learn->audios = $learn_audios;

        $learn->favourited = !empty(FavouriteResource::where('resource_id', $learn->id)->where('user_id', $user_id)->where('type', 'listen_and_learn')->first()) ? true : false;

        unset($learn->created_at);
        unset($learn->updated_at);
        unset($learn->id);
        
        return $learn;
    }

    public function recommended_strategies(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_learns = RecommendedListenAndLearn::where('user_id', $this->user->id);
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
                $learn = ListenAndLearn::find($rec_learn->listen_and_learn_id);
                if(!empty($learn) and ($learn->status == 1)){
                    $learns[] = $this->fetch_strategy($learn, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_learns as $rec_learn){
                $learn = ListenAndLearn::find($rec_learn->listen_and_learn_id);
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
                    $learn = ListenAndLearn::find($key);
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
        $rec_learn = RecommendedListenAndLearn::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_learn)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $learn = ListenAndLearn::find($rec_learn->listen_and_learn_id);
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
        $learn = ListenAndLearn::where('slug', $slug)->first();
        if(empty($learn)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $opened = OpenedListenAndLearn::where('user_id', $this->user->id)->where('listen_and_learn_id', $learn->id)->first();
        if(empty($opened)){
            OpenedListenAndLearn::create([
                'user_id' => $this->user->id,
                'listen_and_learn_id' => $learn->id,
                'frequency' => 1
            ]);
        } else {
            $opened->frequency += 1;
            $opened->save();
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

        $opened_strategies = OpenedListenAndLearn::where('user_id', $this->user->id);
        if($opened_strategies->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy'
            ], 404);
        }

        $learns = [];
        $opened_learns = $opened_strategies->get();
        if(empty($search)){
            foreach($opened_learns as $opened_learn){
                $learn = ListenAndLearn::find($opened_learn->listen_and_learn_id);
                if(!empty($learn) and ($learn->status == 1)){
                    $learns[] = $this->fetch_strategy($learn, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_learns as $opened_learn){
                $learn = ListenAndLearn::find($opened_learn->listen_and_learn_id);
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
                    $learn = ListenAndLearn::find($key);
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

        $learn = ListenAndLearn::where('slug', $slug)->first();
        if(empty($learn) or ($learn->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $opened = OpenedListenAndLearn::where('listen_and_learn_id', $learn->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Listen And Learn Strategy fetched successfully',
            'data' => $this->fetch_strategy($learn, $this->user->id)
        ], 200);
    }

    public function strategy_favourite($slug){
        $learn = ListenAndLearn::where('slug', $slug)->first();
        if(empty($learn)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $action = self::favourite_resource('listen_and_learn', $this->user->id, $learn->id);
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

        $fav_learns = FavouriteResource::where('type', 'listen_and_learn')->where('user_id', $this->user->id);
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
                $learn = ListenAndLearn::find($fav_learn->resource_id);
                if(!empty($learn) and ($learn->status == 1)){
                    $learns[] = $this->fetch_strategy($learn, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_learns as $fav_learn){
                $learn = ListenAndLearn::find($fav_learn->resource_id);
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
                    $learn = ListenAndLearn::find($key);
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
            'message' => 'Favourite  Listen and Learn Strategies fetched successfully',
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

        $learn = ListenAndLearn::where('slug', $slug)->first();
        if(empty($learn) or ($learn->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $fav_learn = FavouriteResource::where('resource_id', $learn->id)->where('user_id', $this->user->id)->where('type', 'listen_and_learn')->first();
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
}
