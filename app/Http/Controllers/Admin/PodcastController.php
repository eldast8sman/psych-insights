<?php

namespace App\Http\Controllers\Admin;

use App\Models\Podcast;
use App\Models\Category;
use App\Models\FileManager;
use Illuminate\Http\Request;
use App\Models\OpenedPodcast;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\StorePodcastRequest;
use App\Http\Requests\Admin\UpdatePodcastRequest;

class PodcastController extends Controller
{
    private $user;
    private $file_disk = 'public';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public static function podcast(Podcast $podcast) : Podcast
    {
        if(!empty($podcast->cover_art)){
            $podcast->photo = FileManagerController::fetch_file($podcast->cover_art);
            unset($podcast->cover_art);
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

        $sub_level = $podcast->subscription_level;
        if($sub_level > 0){
            $package = SubscriptionPackage::where('level', $sub_level)->first();
            $subscription_level = $package->package;
        } else {
            $subscription_level = "Basic";
        }

        $podcast->subscription_level = $subscription_level;

        return $podcast;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = !empty($_GET['search']) ? $_GET['search'] : "";
        $filter = isset($_GET['status']) ? (int)$_GET['status'] : NULL;
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : 'asc';
        $from = !empty($_GET['from']) ? (string)$_GET['from'] : "";
        $to = !empty($_GET['to']) ? (string)$_GET['to'] : "";
        $sort_by = !empty($_GET['sort_by']) ? (string)$_GET['sort_by'] : 'title';
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $podcasts = Podcast::where('status', '>=', 0);
        if(!empty($search)){
            $podcasts = $podcasts->where('title', 'like', '%'.$search.'%');
        }
        if($filter !== NULL){
            $podcasts = $podcasts->where('status', $filter);
        }
        if(!empty($from)){
            $podcasts = $podcasts->where('release_date', '>=', $from);
        }
        if(!empty($to)){
            $podcasts = $podcasts->where('release_date', '<=', $to);
        }
        if((($sort_by == 'title') || ($sort_by == 'release_date')) && (($sort == 'asc') || ($sort == 'desc'))){
            $podcasts = $podcasts->orderBy($sort_by, $sort);
        }

        if($podcasts->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched',
                'data' => null
            ], 200);
        }

        $podcasts = $podcasts->paginate($limit);
        foreach($podcasts as $podcast){
            $podcast = self::podcast($podcast);
        }

        return response([
            'status' => 'failed',
            'message' => 'Podcasts fetched successfully',
            'data' => $podcasts
        ], 200);
    }

    public function summary(){
        $total_podcasts = Podcast::count();
        $total_views = OpenedPodcast::get()->sum('frequency');
        $popular_podcastss = Podcast::orderBy('favourite_count', 'desc')->orderBy('opened_count', 'desc')->limit(5)->get();

        foreach($popular_podcastss as $podcast){
            $podcast = self::podcast($podcast);
        }

        $data = [
            'total_podcasts' => $total_podcasts,
            'total_views' => $total_views,
            'popular_podcastss' => $popular_podcastss
        ];

        return response([
            'status' => 'success',
            'message' => 'Podcast Summary fetched',
            'data' => $data
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePodcastRequest $request)
    {
        $all = $request->except(['cover_art', 'categories']);
        if(!empty($request->cover_art)){
            if(!$upload = FileManagerController::upload_file($request->cover_art, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Podcast Cover Art could not be uploaded'
                ], 500);
            }

            $all['cover_art'] = $upload->id;
        }
        $all['categories'] = join(',', $request->categories);
        if(!$podcast = Podcast::create($all)){
            if(isset($all['cover_art']) && !empty($all['cover_art'])){
                FileManagerController::delete($all['cover_art']);
            }
            return response([
                'status' => 'failed',
                'message' => 'Podcast upload failed'
            ], 500);
        }

        $podcast = self::podcast($podcast);

        return response([
            'status' => 'failed',
            'message' => 'Podcast upload was successful',
            'data' => $podcast
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Podcast $podcast)
    {
        return response([
            'status' => 'success',
            'message' => 'Podcast successfully fetched',
            'data' => self::podcast($podcast)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePodcastRequest $request, Podcast $podcast)
    {
        $old_cover = $podcast->cover_art;
        $all = $request->except(['categories', 'cover_art']);
        if(!empty($request->cover_art)){
            if(!$upload = FileManagerController::upload_file($request->cover_art, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Podcast Art Cover could not be uploaded'
                ], 500);
            }

            $all['cover_art'] = $upload->id;
        }
        $categories = [];
        foreach($request->categories as $cat_id){
            $category = Category::where('id', trim($cat_id))->orWhere('category', trim($cat_id))->first();
            if(!empty($category)){
                if(!in_array($category->id, $categories)){
                    $categories[] = $category->id;
                }
            }
        }
        $all['categories'] = join(',', $categories);
        if(!$podcast->update($all)){
            if(isset($all['cover_art']) && !empty($all['cover_art'])){
                FileManagerController::delete($all['cover_art']);
            }
            return response([
                'status' => 'failed',
                'message' => 'Podcast Update failed'
            ], 500);
        }
        $podcast->update_dependencies();
        if(!empty($old_cover)){
            FileManagerController::delete($old_cover);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcast updated successfully',
            'data' => self::podcast($podcast) 
        ], 200);
    }

    public function activation(Podcast $podcast){
        $podcast->status = ($podcast->status == 0) ? 1 : 0;
        $podcast->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::podcast($podcast)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Podcast $podcast)
    {
        $podcast->delete();
        if(!empty($podcast->cover_art)){
            FileManagerController::delete($podcast->cover_art);
        }

        return response([
            'status' => 'success',
            'message' => 'Podcast successfully deleted'
        ], 200);
    }
}
