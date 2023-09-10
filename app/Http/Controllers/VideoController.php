<?php

namespace App\Http\Controllers;

use App\Models\OpenedResources;
use App\Models\OpenedVideo;
use App\Models\RecommendedVideo;
use App\Models\Video;
use Illuminate\Http\Request;

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
            foreach($rec_videos as $rec_video){
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
            if(($counted < $limit) && (Video::where('status', 1)->where('subscription_level', '<=', $level)->count() >= $limit)){
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
                        'book_id' => $video->id,
                        'slug' => $video->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }
}
