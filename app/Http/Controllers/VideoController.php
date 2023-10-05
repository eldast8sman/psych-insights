<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Category;
use App\Models\OpenedVideo;
use Illuminate\Http\Request;
use App\Models\OpenedResources;
use App\Models\RecommendedVideo;
use App\Models\FavouriteResource;
use App\Models\CurrentSubscription;

class VideoController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public static function recommend_videos($limit, $user_id, $cat_id, $level=0){
        $rec_videos = RecommendedVideo::where('user_id', $user_id);
        if($rec_videos->count() > 0){
            foreach($rec_videos->get() as $rec_video){
                $rec_video->delete();
            }
        }

        if($limit > 0){
            $opened_videos = [];
            $op_videos = OpenedVideo::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_videos->count() > 0){
                foreach($op_videos->get() as $op_video){
                    $opened_videos[] = $op_video->podcast_id;
                }
            }

            $videos_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $videos = Video::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($videos as $video){
                if(count($videos_id) < $first_limit){
                    $categories = explode(',', $video->categories);
                    if(in_array($cat_id, $categories) and !in_array($video->id, $opened_videos)){
                        $videos_id[] = $video;
                        $ids[] = $video->id;
                    }
                } else {
                    break;
                }
            }

            if(count($videos_id) < $first_limit){
                foreach($opened_videos as $opened_video){
                    if(count($videos_id) < $first_limit){
                        $video = Video::find($opened_video);
                        if(!empty($video) && ($video->status == 1)){
                            $categories = explode(',', $video->categories);
                            if(in_array($cat_id, $categories)){
                                $videos_id[] = $video;
                                $ids[] = $video->id;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }

            $counted = count($videos_id);
            if(($counted < $limit) && (Video::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
                $other_videos = Video::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($videos_id)){
                    foreach($videos_id as $video_id){
                        $other_videos = $other_videos->where('id', '<>', $video_id->id);
                    }
                }
                $other_videos = $other_videos->inRandomOrder();
                if($other_videos->count() > 0){
                    $other_videos = $other_videos->get(['id', 'slug']);
                    foreach($other_videos as $other_video){
                        if(count($videos_id) < $limit){
                            if(!in_array($other_video->id, $opened_videos)){
                                $videos_id[] = $other_video;
                                $ids[] = $other_video->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($videos_id) < $limit){
                        foreach($other_videos as $other_video){
                            if(count($videos_id) < $limit){
                                if(!in_array($other_video->id, $ids)){
                                    $videos_id[] = $other_video;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            if(!empty($videos_id)){
                foreach($videos_id as $video){
                    RecommendedVideo::create([
                        'user_id' => $user_id,
                        'video_id' => $video->id,
                        'slug' => $video->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }

    public function fetch_video(Video $video) : Video
    {
        if(!empty($video->photo)){
            $video->photo = FileManagerController::fetch_file($video->photo);
        }
        if(!empty($video->video)){
            $video->video = FileManagerController::fetch_file($video->video);
        }

        if(!empty($video->categories)){
            $categories = [];

            $categs = explode(',', $video->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $video->categories = $categories;
        }

        unset($video->created_at);
        unset($video->updated_at);
        unset($video->id);

        return $video;
    }

    public function recommended_videos(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_videos = RecommendedVideo::where('user_id', $this->user->id);
        if($rec_videos->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Recommended Video',
                'data' => $rec_videos->paginate($limit)
            ], 200);
        }

        $videos = [];
        $rec_videos = $rec_videos->get();
        if(empty($search)){
            foreach($rec_videos as $rec_video){
                $video = Video::find($rec_video->video_id);
                if(!empty($video) && ($video->status == 1)){
                    $videos[] = $this->fetch_video($video);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_videos as $rec_video){
                $video = Video::find($rec_video->video_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($video->title, $word) !== FALSE) or (strpos($video->description, $word) !== FALSE)){
                        $count += 1;

                    }
                }
                if($count > 0){
                    $recommendeds[$video->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $video = Video::find($key);
                    if(!empty($video) and ($video->status == 1)){
                        $videos[] = $this->fetch_video($video);
                    }
                }
            }
        }

        if(empty($videos)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was found',
                'data' => self::paginate_array(array_values($videos), $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Videos fetched successfully',
            'data' => self::paginate_array(array_values($videos), $limit, $page)
        ], 200);
    }

    public function recommended_video($slug){
        $rec_video = RecommendedVideo::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_video)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was fetched'
            ], 404);
        }

        $video = Video::find($rec_video->video_id);
        if(empty($video) or ($video->status != 1)){
            return response([
                'ststus' => 'failed',
                'message' => 'No Video was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Video fetched successfully',
            'data' => $this->fetch_video($video)
        ], 200);
    }

    public function mark_as_opened($slug){
        $video = Video::where('slug', $slug)->first();
        if(empty($video)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was fetched'
            ], 404);
        }

        $opened = OpenedVideo::where('user_id', $this->user->id)->where('video_id', $video->id)->first();
        if(empty($opened)){
            OpenedVideo::create([
                'user_id' => $this->user->id,
                'video_id' => $video->id,
                'frequency' => 1
            ]);
        } else {
            $opened->frequency += 1;
            $opened->save();
        }

        $video->opened_count += 1;
        $video->save();

        return response([
            'status' => 'success',
            'message' => 'Marked as Opened'
        ], 200);
    }

    public function opened_videos(){
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

        $opened_videos = OpenedVideo::where('user_id', $this->user->id);
        if($opened_videos->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Video',
                'data' => $opened_videos->paginate($limit)
            ], 200);
        }

        $videos = [];
        $opened_videos = $opened_videos->get();
        if(empty($search)){
            foreach($opened_videos as $opened_video){
                $video = Video::find($opened_video->video_id);
                if(!empty($video) && ($video->status == 1)){
                    $videos[] = $this->fetch_video($video);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_videos as $opened_video){
                $video = Video::find($opened_video->video_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($video->title, $word) !== FALSE) or (strpos($video->description, $word) !== FALSE)){
                        $count += 1;

                    }
                }
                if($count > 0){
                    $recommendeds[$video->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $video = Video::find($key);
                    if(!empty($video) and ($video->status == 1)){
                        $videos[] = $this->fetch_video($video);
                    }
                }
            }
        }

        if(empty($videos)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was found',
                'data' => self::paginate_array(array_values($videos), $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Videos fetched successfully',
            'data' => self::paginate_array(array_values($videos), $limit, $page)
        ], 200);
    }

    public function opened_video($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $video = Video::where('slug', $slug)->first();
        if(empty($video) or ($video->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was fetched'
            ], 404);
        }

        $opened = OpenedVideo::where('video_id', $video->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Video fetched successfully',
            'data' => $this->fetch_video($video)
        ], 200);
    }

    public function video_favourite($slug){
        $video = Video::where('slug', $slug)->first();
        if(empty($video)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was fetched'
            ], 404);
        }

        $action = self::favourite_resource('video', $this->user->id, $video->id);
        if($action == 'saved'){
            $video->favourite_count += 1;
        } else {
            $video->favourite_count -= 1;
        }
        $video->save();
        $message = ($action == 'saved') ? 'Video added to Favourites' : 'Video removed from Favourites';

        return response([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function favourite_videos(){
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

        $fav_videos = FavouriteResource::where('type', 'video')->where('user_id', $this->user->id);
        if($fav_videos->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Video',
                'data' => $fav_videos->paginate($limit)
            ], 200);
        }

        $videos = [];
        $fav_videos = $fav_videos->get();
        if(empty($search)){
            foreach($fav_videos as $fav_video){
                $video = Video::find($fav_video->resource_id);
                if(!empty($video) && ($video->status == 1)){
                    $videos[] = $this->fetch_video($video);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_videos as $fav_video){
                $video = Video::find($fav_video->resource_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($video->title, $word) !== FALSE) or (strpos($video->description, $word) !== FALSE)){
                        $count += 1;

                    }
                }
                if($count > 0){
                    $recommendeds[$video->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $video = Video::find($key);
                    if(!empty($video) and ($video->status == 1)){
                        $videos[] = $this->fetch_video($video);
                    }
                }
            }
        }

        if(empty($videos)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was found',
                'data' => self::paginate_array(array_values($videos), $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Videos fetched successfully',
            'data' => self::paginate_array(array_values($videos), $limit, $page)
        ], 200);
    }

    public function favourite_video($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $video = Video::where('slug', $slug)->first();
        if(empty($video) or ($video->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was fetched'
            ], 404);
        }

        $fav_video = FavouriteResource::where('resource_id', $video->id)->where('user_id', $this->user->id)->where('type', 'video')->first();
        if(empty($fav_video)){
            return response([
                'status' => 'failed',
                'message' => 'No Video was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Video fetched successfully',
            'data' => $this->fetch_video($video)
        ], 200);
    }
}
