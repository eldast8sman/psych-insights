<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ReadAndReflect;
use App\Models\FavouriteResource;
use App\Models\CurrentSubscription;
use App\Models\OpenedReadAndReflect;
use App\Models\ReadAndReflectAnswer;
use App\Models\ReadAndReflectReflection;
use App\Models\RecommendedReadAndReflect;
use App\Http\Requests\AnswerReadAndReflectRequest;

class ReadAndReflectController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api', ['except' => ['recommend_strategies']]);
        $this->user = AuthController::user();
    }

    public static function recommend_strategies($limit, $user_id, $cat_id, $sec_cat_id, $level=0){
        $rec_strategies = RecommendedReadAndReflect::where('user_id', $user_id);
        if($rec_strategies->count() > 0){
            foreach($rec_strategies->get() as $rec_strategy){
                $rec_strategy->delete();
            }
        }

        if($limit > 0){
            $opened_strategies = [];
            $op_strategies = OpenedReadAndReflect::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_strategies->count() > 0){
                foreach($op_strategies as $op_strategy){
                    $opened_strategies[] = $op_strategy->read_and_reflect_id;
                }
            }

            $strategies_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $reads = ReadAndReflect::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($reads as $read){
                if(count($strategies_id) < $first_limit){
                    $categories = explode(',', $read->categories);
                    if(in_array($cat_id, $categories) and !in_array($read->id, $opened_strategies)){
                        $strategies_id[] = $read;
                        $ids[] = $read->id;
                    }
                } else {
                    break;
                }
            }

            if(count($strategies_id) < $first_limit){
                foreach($opened_strategies as $opened_strategy){
                    if(count($strategies_id) < $first_limit){
                        $read = ReadAndReflect::find($opened_strategy);
                        if(!empty($read) and ($read->status == 1) and ($read->subscription_level <= $level)){
                            $categories = explode(',', $read->categories);
                            if(in_array($cat_id, $categories)){
                                $strategies_id[] = $read;
                                $ids[] = $read->id;
                            }                            
                        }
                    } else {
                        break;
                    }
                }
            }

            $counting = count($strategies_id);
            if(($counting < $limit) and (ReadAndReflect::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counting)){
                $s_reads = ReadAndReflect::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($strategies_id)){
                    foreach($strategies_id as $strategy_id){
                        $s_reads = $s_reads->where('id', '<>', $strategy_id->id);
                    }
                }
                $s_reads = $s_reads->orderBy('created_at', 'desc')->get(['id', 'slug', 'categories']);
                foreach($s_reads as $read){
                    if(count($strategies_id) < $limit){
                        $categories = explode(',', $read->categories);
                        if(in_array($sec_cat_id, $categories) and !in_array($read->id, $opened_strategies)){
                            $strategies_id[] = $read;
                            $ids[] = $read->id;
                        }
                    } else {
                        break;
                    }
                }

                if(count($strategies_id) < $limit){
                    foreach($opened_strategies as $opened_strategy){
                        if(count($strategies_id) < $limit){
                            $read = ReadAndReflect::find($opened_strategy);
                            if(!empty($read) and ($read->status == 1) and ($read->subscription_level <= $level)){
                                $categories = explode(',', $read->categories);
                                if(in_array($sec_cat_id, $categories)){
                                    $strategies_id[] = $read;
                                    $ids[] = $read->id;
                                }
                            }
                        } else {
                            break;
                        }
                    }
                }
            }

            $counted = count($strategies_id);
            if(($counted < $limit) and (ReadAndReflect::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
                $other_reads = ReadAndReflect::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($strategies_id)){
                    foreach($strategies_id as $strategy_id){
                        $other_reads = $other_reads->where('id', '<>', $strategy_id->id);
                    }
                }
                $other_reads = $other_reads->inRandomOrder();
                if($other_reads->count() > 0){
                    $other_reads = $other_reads->get(['id', 'slug']);
                    foreach($other_reads as $other_read){
                        if(count($strategies_id) < $limit){
                            if(!in_array($other_read->id, $opened_strategies)){
                                $strategies_id[] = $other_read;
                                $ids[] = $other_read->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($strategies_id) < $limit){
                        foreach($other_reads as $other_read){
                            if(count($strategies_id) < $limit){
                                if(!in_array($other_read->id, $ids)){
                                    $strategies_id[] = $other_read;
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
                    RecommendedReadAndReflect::create([
                        'user_id' => $user_id,
                        'read_and_reflect_id' => $strategy->id,
                        'slug' => $strategy->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }

    public function fetch_strategy(ReadAndReflect $read, $user_id) : ReadAndReflect
    {
        if(!empty($read->photo)){
            $read->photo = FileManagerController::fetch_file($read->photo);
        }

        if(!empty($read->categories)){
            $categories = [];

            $categs = explode(',', $read->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $read->categories = $categories;
        }
        $read->reflections = ReadAndReflectReflection::where('read_and_reflect_id', $read->id)->get();

        $read->favourited = !empty(FavouriteResource::where('resource_id', $read->id)->where('user_id', $user_id)->where('type', 'read_and_reflect')->first()) ? true : false;

        unset($read->id);
        unset($read->created_at);
        unset($read->updated_at);

        return $read;
    }

    public function recommended_strategies(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_reads = RecommendedReadAndReflect::where('user_id', $this->user->id);
        if($rec_reads->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Recommended Strategy',
                'data' => $rec_reads->paginate($limit)
            ], 200);
        }

        $reads = [];
        $rec_reads = $rec_reads->get();
        if(empty($search)){
            foreach($rec_reads as $rec_read){
                $read = ReadAndReflect::find($rec_read->read_and_reflect_id);
                if(!empty($read) and ($read->status == 1)){
                    $reads[] = $this->fetch_strategy($read, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_reads as $rec_read){
                $read = ReadAndReflect::find($rec_read->read_and_reflect_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($read->title, $word) !== FALSE) or (strpos($read->overview, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$read->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $read = ReadAndReflect::find($key);
                    if(!empty($read) and ($read->status == 1)){
                        $reads[] = $this->fetch_strategy($read, $this->user->id);
                    }
                }
            }
        }

        if(empty($reads)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was found',
                'data' => self::paginate_array($reads, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Strategies fetched successfully',
            'data' => self::paginate_array($reads, $limit, $page)
        ], 200);
    }

    public function recommended_strategy($slug){
        $rec_read = RecommendedReadAndReflect::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_read)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $read = ReadAndReflect::find($rec_read->read_and_reflect_id);
        if(empty($read) or ($read->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Strategy fetched successfully',
            'data' => $this->fetch_strategy($read, $this->user->id)
        ], 200);
    }

    public function mark_as_opened($slug){
        $read = ReadAndReflect::where('slug', $slug)->first();
        if(empty($read)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $opened = OpenedReadAndReflect::where('user_id', $this->user->id)->where('read_and_reflect_id', $read->id)->first();
        if(empty($opened)){
            OpenedReadAndReflect::create([
                'user_id' => $this->user->id,
                'read_and_reflect_id' => $read->id,
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

        $opened_strategies = OpenedReadAndReflect::where('user_id', $this->user->id);
        if($opened_strategies->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy'
            ], 404);
        }

        $reads = [];
        $opened_reads = $opened_strategies->get();
        if(empty($search)){
            foreach($opened_reads as $opened_read){
                $read = ReadAndReflect::find($opened_read->read_and_reflect_id);
                if(!empty($read) and ($read->status == 1)){
                    $reads[] = $this->fetch_strategy($read, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_reads as $opened_read){
                $read = ReadAndReflect::find($opened_read->read_and_reflect_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($read->title, $word) !== FALSE) or (strpos($read->overview, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$read->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $read = ReadAndReflect::find($key);
                    if(!empty($read) and ($read->status == 1)){
                        $reads[] = $this->fetch_strategy($read, $this->user->id);
                    }
                }
            }
        }

        if(empty($reads)){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy was found',
                'data' => self::paginate_array($reads, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Opened Read and Reflect Strategies fetched successfully',
            'data' => self::paginate_array($reads, $limit, $page)
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

        $read = ReadAndReflect::where('slug', $slug)->first();
        if(empty($read) or ($read->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $opened = OpenedReadAndReflect::where('read_and_reflect_id', $read->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Strategy was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Read and Reflect Strategy fetched successfully',
            'data' => $this->fetch_strategy($read, $this->user->id)
        ], 200);
    }

    public function strategy_favourite($slug){
        $read = ReadAndReflect::where('slug', $slug)->first();
        if(empty($read)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $action = self::favourite_resource('read_and_reflect', $this->user->id, $read->id);
        if($action == 'saved'){
            $read->favourite_count += 1;
        } else {
            $read->favourite_count -= 1;
        }
        $read->save();
        $read->update_dependencies();
        
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

        $fav_reads = FavouriteResource::where('type', 'read_and_reflect')->where('user_id', $this->user->id);
        if($fav_reads->count() < 1){
            return response([
                'staatus' => 'failed',
                'message' => 'No Favourite Strategy was fetched',
                'data' => $fav_reads->paginate($limit)
            ], 200);
        }

        $reads = [];
        $fav_reads = $fav_reads->get();
        if(empty($search)){
            foreach($fav_reads as $fav_read){
                $read = ReadAndReflect::find($fav_read->resource_id);
                if(!empty($read) and ($read->status == 1)){
                    $reads[] = $this->fetch_strategy($read, $this->user->id);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_reads as $fav_read){
                $read = ReadAndReflect::find($fav_read->read_and_reflect_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($read->title, $word) !== FALSE) or (strpos($read->overview, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$read->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $read = ReadAndReflect::find($key);
                    if(!empty($read) and ($read->status == 1)){
                        $reads[] = $this->fetch_strategy($read, $this->user->id);
                    }
                }
            }
        }

        if(empty($learns)){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Strategy was found',
                'data' => self::paginate_array($reads, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Favourite Read and Reflect Strategies fetched successfully',
            'data' => self::paginate_array($reads, $limit, $page)
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

        $read = ReadAndReflect::where('slug', $slug)->first();
        if(empty($read) or ($read->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched'
            ], 404);
        }

        $fav_read = FavouriteResource::where('resource_id', $read->id)->where('user_id', $this->user->id)->where('type', 'read_and_reflect')->first();
        if(empty($fav_read)){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Strategy was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Read and Reflect Strategy fetched successfully',
            'data' => $this->fetch_strategy($read, $this->user->id)
        ], 200);
    }

    public function answer_reflections(AnswerReadAndReflectRequest $request, $slug){
        $reflect = ReadAndReflect::where('slug', $slug)->first();
        if(empty($reflect)){
            return response([
                'status' => 'failed',
                'message' => 'Read and Reflect not found'
            ], 404);
        }

        $answers = [];
        foreach($request->answers as $answer){
            $reflection = ReadAndReflectReflection::find($answer['reflection_id']);
            if(!empty($reflection) and ($reflection->read_and_reflect_id == $reflect->id)){
                $answers[] = [
                    'reflection_id' => $answer['reflection_id'],
                    'question' => $reflection->reflection,
                    'answer' => $answer['answer']
                ];
            }
        }

        $answered = ReadAndReflectAnswer::create([
            'user_id' => $this->user->id,
            'read_and_reflect_id' => $reflect->id,
            'answers' => json_encode($answers)
        ], 200);

        $answered->answers = json_decode($answered->answers, true);

        return response([
            'status' => 'success',
            'message' => 'Reflection successful',
            'data' => $answered
        ], 200);
    }

    public function previous_answers($slug){
        $reflect = ReadAndReflect::where('slug', $slug)->first();
        if(empty($reflect)){
            return response([
                'status' => 'failed',
                'message' => 'Read and Reflect not found'
            ], 404);
        }

        $answers = ReadAndReflectAnswer::where('read_and_reflect_id', $reflect->id)->where('user_id', $this->user->id)->orderBy('created_at', 'desc');
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
