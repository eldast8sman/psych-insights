<?php

namespace App\Http\Controllers;

use App\Models\Podcast;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\OpenedPodcast;
use App\Models\OpenedResources;
use App\Models\FavouriteResource;
use App\Models\RecommendedPodcast;
use App\Models\CurrentSubscription;

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
            foreach($rec_podcasts->get() as $rec_podcast){
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
            if(($counted < $limit) && (Podcast::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
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
                        'podcast_id' => $podcast->id,
                        'slug' => $podcast->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }

    public static function fetch_podcast(Podcast $podcast) : Podcast
    {
        if(!empty($podcast->cover_art)){
            $podcast->cover_art = FileManagerController::fetch_file($podcast->cover_art);
        }

        if(!empty($podcast->categories)){
            $categories = [];

            $categs = explode(',', $podcast->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $podcast->categories = $categories;
        }

        unset($podcast->id);
        unset($podcast->created_at);
        unset($podcast->updated_at);

        return $podcast;
    }

    public function recommended_podcasts(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_podcasts = RecommendedPodcast::where('user_id', $this->user->id);
        if($rec_podcasts->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Recommended Podcast',
                'data' => $rec_podcasts->paginate($limit)
            ], 200);
        }

        $podcasts = [];
        $rec_podcasts = $rec_podcasts->get();
        if(empty($search)){
            foreach($rec_podcasts as $rec_podcast){
                $podcast = Podcast::find($rec_podcast->podcast_id);
                if(!empty($podcast) && ($podcast->status == 1)){
                    $podcasts[] = $this->fetch_podcast($podcast);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_podcasts as $rec_podcast){
                $podcast = Podcast::find($rec_podcast->podcast_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($podcast->title, $word) !== FALSE) or (strpos($podcast->summary, $word) !== FALSE) or (strpos($podcast->summary, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$podcast->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $podcast = Podcast::find($key);
                    if(!empty($podcast) and ($podcast->status == 1)){
                        $podcasts[] = $this->fetch_podcast($podcast);
                    }
                }
            }
        }

        if(empty($podcasts)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was found',
                'data' => self::paginate_array($podcasts, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcasts fetched successfully',
            'data' => self::paginate_array($podcasts, $limit, $page)
        ], 200);
    }

    public function recommended_podcast($slug){
        $rec_podcast = RecommendedPodcast::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_podcast)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was fetched'
            ], 404);
        }

        $podcast = Podcast::find($rec_podcast->podcast_id);
        if(empty($podcast) or ($podcast->status != 1)){
            return response([
                'ststus' => 'failed',
                'message' => 'No Podcast was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcast fetched successfully',
            'data' => $this->fetch_podcast($podcast)
        ], 200);
    }

    public function mark_as_opened($slug){
        $podcast = Podcast::where('slug', $slug)->first();
        if(empty($podcast)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was fetched'
            ], 404);
        }

        $opened = OpenedPodcast::where('user_id', $this->user->id)->where('podcast_id', $podcast->id)->first();
        if(empty($opened)){
            OpenedPodcast::create([
                'user_id' => $this->user->id,
                'podcast_id' => $podcast->id,
                'frequency' => 1
            ]);
        } else {
            $opened->frequency += 1;
            $opened->save();
        }
        $podcast->opened_count += 1;
        $podcast->save();

        return response([
            'status' => 'success',
            'message' => 'Marked as Opened'
        ], 200);
    }

    public function opened_podcasts(){
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

        $opened_podcasts = OpenedPodcast::where('user_id', $this->user->id);
        if($opened_podcasts->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Podcast',
                'data' => $opened_podcasts->paginate($limit)
            ], 200);
        }

        $books = [];
        $opened_podcasts = $opened_podcasts->get();
        if(empty($search)){
            foreach($opened_podcasts as $opened_podcast){
                $podcast = Podcast::find($opened_podcast->podcast_id);
                if(!empty($podcast) && ($podcast->status == 1)){
                    $podcasts[] = $this->fetch_podcast($podcast);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_podcasts as $opened_podcast){
                $podcast = Podcast::find($opened_podcast->podcast_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($podcast->title, $word) !== FALSE) or (strpos($podcast->summary, $word) !== FALSE) or (strpos($podcast->summary, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$podcast->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $podcast = Podcast::find($key);
                    if(!empty($podcast) and ($podcast->status == 1)){
                        $podcasts[] = $this->fetch_podcast($podcast);
                    }
                }
            }
        }


        if(empty($podcasts)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was found',
                'data' => self::paginate_array($podcasts, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcasts fetched successfully',
            'data' => self::paginate_array($podcasts, $limit, $page)
        ], 200);
    }

    public function opened_podcast($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $podcast = Podcast::where('slug', $slug)->first();
        if(empty($podcast) or ($podcast->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was fetched'
            ], 404);
        }

        $opened = OpenedPodcast::where('podcast_id', $podcast->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcast fetched successfully',
            'data' => $this->fetch_podcast($podcast)
        ], 200);
    }

    public function podcast_favourite($slug){
        $podcast = Podcast::where('slug', $slug)->first();
        if(empty($podcast)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was fetched'
            ], 404);
        }

        $action = self::favourite_resource('podcast', $this->user->id, $podcast->id);
        if($action == 'saved'){
            $podcast->favourite_count += 1;
        } else {
            $podcast->favourite_count -= 1;
        }
        $podcast->save();
        $message = ($action == 'saved') ? 'Podcast added to Favourites' : 'Podcast removed from Favourites';

        return response([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function favourite_podcasts(){
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

        $fav_podcasts = FavouriteResource::where('type', 'podcast')->where('user_id', $this->user->id);
        if($fav_podcasts->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Podcast',
                'data' => $fav_podcasts->paginate($limit)
            ], 200);
        }

        $podcasts = [];
        $fav_podcasts = $fav_podcasts->get();
        if(empty($search)){
            foreach($fav_podcasts as $fav_podcast){
                $podcast = Podcast::find($fav_podcast->resource_id);
                if(!empty($podcast) && ($podcast->status == 1)){
                    $podcasts[] = $this->fetch_podcast($podcast);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_podcasts as $fav_podcast){
                $podcast = Podcast::find($fav_podcast->resource_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($podcast->title, $word) !== FALSE) or (strpos($podcast->summary, $word) !== FALSE) or (strpos($podcast->summary, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$podcast->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $podcast = Podcast::find($key);
                    if(!empty($podcast) and ($podcast->status == 1)){
                        $podcasts[] = $this->fetch_podcast($podcast);
                    }
                }
            }
        }

        if(empty($podcasts)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was found',
                'data' => self::paginate_array($podcasts, $limit, $page)
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcasts fetched successfully',
            'data' => self::paginate_array($podcasts, $limit, $page)
        ], 200);
    }

    public function favourite_podcast($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $podcast = Podcast::where('slug', $slug)->first();
        if(empty($podcast) or ($podcast->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched'
            ], 404);
        }

        $fav_podcast = FavouriteResource::where('resource_id', $podcast->id)->where('user_id', $this->user->id)->where('type', 'podcast')->first();
        if(empty($fav_podcast)){
            return response([
                'status' => 'failed',
                'message' => 'No Podcast was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcast fetched successfully',
            'data' => $this->fetch_podcast($podcast)
        ], 200);
    }
}
