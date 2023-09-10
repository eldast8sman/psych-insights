<?php

namespace App\Http\Controllers;

use App\Models\OpenedPodcast;
use App\Models\Podcast;
use Illuminate\Http\Request;
use App\Models\OpenedResources;
use App\Models\RecommendedPodcast;

class PodcastController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }
    
    public static function recommend_podcasts($limit, $user_id, $cat_id, $level=0){
        $rec_podcasts = RecommendedPodcast::where('user_id', $user_id);
        if($rec_podcasts->count() > 0){
            foreach($rec_podcasts as $rec_podcast){
                $rec_podcast->delete();
            }
        }

        if($limit > 0){
            $opened_podcasts = [];
            $op_podcasts = OpenedPodcast::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_podcasts->count() > 0){
                foreach($op_podcasts->get() as $op_podcast){
                    $opened_podcasts[] = $op_podcast->podcast_id;
                }
            }

            $podcasts_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $podcasts = Podcast::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($podcasts as $podcast){
                if(count($podcasts_id) < $first_limit){
                    $categories = explode(',', $podcast->categories);
                    if(in_array($cat_id, $categories) and !in_array($podcast->id, $opened_podcasts)){
                        $podcasts_id[] = $podcast;
                        $ids[] = $podcast->id;
                    }
                } else {
                    break;
                }
            }

            if(count($podcasts_id) < $first_limit){
                foreach($opened_podcasts as $opened_podcast){
                    if(count($podcasts_id) < $first_limit){
                        $podcast = Podcast::find($opened_podcast);
                        if(!empty($podcast) && ($podcast->status == 1) && ($podcast->subscription_level <= $level)){
                            $categories = explode(',', $podcast->categories);
                            if(in_array($cat_id, $categories)){
                                $podcasts_id[] = $podcast;
                                $ids[] = $podcast->id;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }

            $counted = count($podcasts_id);
            if(($counted < $limit) && (Podcast::where('status', 1)->where('subscription_level', '<=', $level)->count() >= $limit)){
                $other_podcasts = Podcast::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($podcasts_id)){
                    foreach($podcasts_id as $podcast_id){
                        $other_podcasts = $other_podcasts->where('id', '<>', $podcast_id->id);
                    }
                }
                $other_podcasts = $other_podcasts->inRandomOrder();
                if($other_podcasts->count() > 0){
                    $other_podcasts = $other_podcasts->get(['id', 'slug']);
                    foreach($other_podcasts as $other_podcast){
                        if(count($podcasts_id) < $limit){
                            if(!in_array($other_podcast->id, $opened_podcasts)){
                                $podcasts_id[] = $other_podcast;
                                $ids[] = $other_podcast->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($podcasts_id) < $limit){
                        foreach($other_podcasts as $other_podcast){
                            if(count($podcasts_id) < $limit){
                                if(!in_array($other_podcast->id, $ids)){
                                    $podcasts_id[] = $other_podcast;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            if(!empty($podcasts_id)){
                foreach($podcasts_id as $podcast){
                    RecommendedPodcast::create([
                        'user_id' => $user_id,
                        'book_id' => $podcast->id,
                        'slug' => $podcast->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }
}
