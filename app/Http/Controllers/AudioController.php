<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Category;
use App\Models\OpenedAudio;
use Illuminate\Http\Request;
use App\Models\OpenedResources;
use App\Models\RecommendedAudio;
use App\Models\FavouriteResource;
use App\Models\CurrentSubscription;

class AudioController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public static function recommend_audios($limit, $user_id, $cat_id, $level=0){
        $rec_audios = RecommendedAudio::where('user_id', $user_id);
        if($rec_audios->count() > 0){
            foreach($rec_audios->get() as $rec_audio){
                $rec_audio->delete();
            }
        }

        if($limit > 0){
            $opened_audios = [];
            $op_audios = OpenedAudio::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_audios->count() > 0){
                foreach($op_audios->get() as $op_audio){
                    $opened_audios[] = $op_audio->audio_id;
                }
            }

            $audios_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $audios = Audio::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($audios as $audio){
                if(count($audios_id) < $first_limit){
                    $categories = explode(',', $audio->categories);
                    if(in_array($cat_id, $categories) and !in_array($audio->id, $opened_audios)){
                        $audios_id[] = $audio;
                        $ids[] = $audio->id;
                    }
                } else {
                    break;
                }
            }

            if(count($audios_id) < $first_limit){
                foreach($opened_audios as $opened_audio){
                    if(count($audios_id) < $first_limit){
                        $audio = Audio::find($opened_audio);
                        if(!empty($audio) && ($audio->status == 1) && ($audio->subscription_level <= $level)){
                            $categories = explode(',', $audio->categories);
                            if(in_array($cat_id, $categories)){
                                $audios_id[] = $audio;
                                $ids[] = $audio->id;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }

            $counted = count($audios_id);
            if(($counted < $limit) && (Audio::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
                $other_audios = Audio::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($audios_id)){
                    foreach($audios_id as $audio_id){
                        $other_audios = $other_audios->where('id', '<>', $audio_id->id);
                    }
                }
                $other_audios = $other_audios->inRandomOrder();
                if($other_audios->count() > 0){
                    $other_audios = $other_audios->get(['id', 'slug']);
                    foreach($other_audios as $other_audio){
                        if(count($audios_id) < $limit){
                            if(!in_array($other_audio->id, $opened_audios)){
                                $audios_id[] = $other_audio;
                                $ids[] = $other_audio->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($audios_id) < $limit){
                        foreach($other_audios as $other_audio){
                            if(count($audios_id) < $limit){
                                if(!in_array($other_audio->id, $ids)){
                                    $audios_id[] = $other_audio;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            if(!empty($audios_id)){
                foreach($audios_id as $audio){
                    RecommendedAudio::create([
                        'user_id' => $user_id,
                        'audio_id' => $audio->id,
                        'slug' => $audio->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }

    public function fetch_audio(Audio $audio) : Audio
    {
        if(!empty($audio->audio)){
            $audio->audio = FileManagerController::fetch_file($audio->audio);
        }

        if(!empty($audio->categories)){
            $categories = [];

            $categs = explode(',', $audio->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $audio->categories = $categories;
        }

        unset($audio->created_at);
        unset($audio->updated_at);
        unset($audio->id);

        return $audio;
    }

    public function recommended_audios(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_audios = RecommendedAudio::where('user_id', $this->user->id);
        if($rec_audios->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Recommended Audio',
                'data' => $rec_audios->paginate($limit)
            ], 200);
        }

        $audios = [];
        $rec_audios = $rec_audios->get();
        if(empty($search)){
            foreach($rec_audios as $rec_audio){
                $audio = Audio::find($rec_audio->audio_id);
                if(!empty($audio) && ($audio->status == 1)){
                    $audios[] = $this->fetch_audio($audio);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_audios as $rec_audio){
                $audio = Audio::find($rec_audio->audio_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($audio->title, $word) !== FALSE) or (strpos($audio->description, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$audio->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $audio = Audio::find($key);
                    if(!empty($audio) and ($audio->status == 1)){
                        $audios[] = $this->fetch_audio($audio);
                    }
                }
            }
        }

        if(empty($audios)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was found',
                'data' => self::paginate_array($audios, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio fetched successfully',
            'data' => self::paginate_array($audios, $limit, $page)
        ], 200);
    }

    public function recommended_audio($slug){
        $rec_audio = RecommendedAudio::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_audio)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was fetched'
            ], 404);
        }

        $audio = Audio::find($rec_audio->audio_id);
        if(empty($audio) or ($audio->status != 1)){
            return response([
                'ststus' => 'failed',
                'message' => 'No Audio was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio fetched successfully',
            'data' => $this->fetch_audio($audio)
        ], 200);
    }

    public function mark_as_opened($slug){
        $audio = Audio::where('slug', $slug)->first();
        if(empty($audio)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was fetched'
            ], 404);
        }

        $opened = OpenedAudio::where('user_id', $this->user->id)->where('audio_id', $audio->id)->first();
        if(empty($opened)){
            OpenedAudio::create([
                'user_id' => $this->user->id,
                'audio_id' => $audio->id,
                'frequency' => 1
            ]);
        } else {
            $opened->frequency += 1;
            $opened->save();
        }

        $audio->opened_count += 1;
        $audio->save();

        return response([
            'status' => 'success',
            'message' => 'Marked as Opened'
        ], 200);
    }

    public function opened_audios(){
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

        $opened_audios = OpenedAudio::where('user_id', $this->user->id);
        if($opened_audios->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Audio',
                'data' => $opened_audios->paginate($limit)
            ], 200);
        }

        $audios = [];
        $opened_audios = $opened_audios->get();
        if(empty($search)){
            foreach($opened_audios as $opened_audio){
                $audio = Audio::find($opened_audio->audio_id);
                if(!empty($audio) && ($audio->status == 1)){
                    $audios[] = $this->fetch_audio($audio);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_audios as $opened_audio){
                $audio = Audio::find($opened_audio->audio_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($audio->title, $word) !== FALSE) or (strpos($audio->description, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$audio->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $audio = Audio::find($key);
                    if(!empty($audio) and ($audio->status == 1)){
                        $audios[] = $this->fetch_audio($audio);
                    }
                }
            }
        }

        if(empty($audios)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was found',
                'data' => self::paginate_array($audios, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Audios fetched successfully',
            'data' => self::paginate_array($audios, $limit, $page)
        ], 200);
    }

    public function opened_audio($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $audio = Audio::where('slug', $slug)->first();
        if(empty($audio) or ($audio->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was fetched'
            ], 404);
        }

        $opened = OpenedAudio::where('audio_id', $audio->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio fetched successfully',
            'data' => $this->fetch_audio($audio)
        ], 200);
    }

    public function audio_favourite($slug){
        $audio = Audio::where('slug', $slug)->first();
        if(empty($audio)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was fetched'
            ], 404);
        }

        $action = self::favourite_resource('audio', $this->user->id, $audio->id);
        if($action == 'saved'){
            $audio->favourite_count += 1;
        } else {
            $audio->favourite_count -= 1;
        }
        $audio->save();
        $message = ($action == 'saved') ? 'Audio added to Favourites' : 'Audio removed from Favourites';

        return response([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function favourite_audios(){
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

        $fav_audios = FavouriteResource::where('type', 'audio')->where('user_id', $this->user->id);
        if($fav_audios->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Audio',
                'data' => $fav_audios->paginate($limit)
            ], 200);
        }

        $audios = [];
        $fav_audios = $fav_audios->get();
        if(empty($search)){
            foreach($fav_audios as $fav_audio){
                $audio = Audio::find($fav_audio->resource_id);
                if(!empty($audio) && ($audio->status == 1)){
                    $audios[] = $this->fetch_audio($audio);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_audios as $fav_audio){
                $audio = Audio::find($fav_audio->resource_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($audio->title, $word) !== FALSE) or (strpos($audio->description, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$audio->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $audio = Audio::find($key);
                    if(!empty($audio) and ($audio->status == 1)){
                        $audios[] = $this->fetch_audio($audio);
                    }
                }
            }
        }

        if(empty($audios)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was found',
                'data' => self::paginate_array($audios, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio fetched successfully',
            'data' => self::paginate_array($audios, $limit, $page)
        ], 200);
    }

    public function favourite_audio($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $audio = Audio::where('slug', $slug)->first();
        if(empty($audio) or ($audio->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was fetched'
            ], 404);
        }

        $fav_audio = FavouriteResource::where('resource_id', $audio->id)->where('user_id', $this->user->id)->where('type', 'audio')->first();
        if(empty($fav_audio)){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio fetched successfully',
            'data' => $this->fetch_audio($audio)
        ], 200);
    }
}
