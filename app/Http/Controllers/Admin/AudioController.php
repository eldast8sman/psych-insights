<?php

namespace App\Http\Controllers\Admin;

use App\Models\Audio;
use App\Models\Category;
use App\Models\OpenedAudio;
use Illuminate\Http\Request;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAudioRequest;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\UpdateAudioRequest;
use App\Models\FileManager;

class AudioController extends Controller
{
    private $user;
    private $file_disk = 's3';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public static function audio(Audio $audio) : Audio
    {
        if(!empty($audio->photo)){
            $audio->photo = FileManagerController::fetch_file($audio->photo);
        }

        if(!empty($audio->audio_file)){
            $audio->audio_file = FileManagerController::fetch_file($audio->audio_file);
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

        $sub_level = $audio->subscription_level;
        if($sub_level > 0){
            $package = SubscriptionPackage::where('level', $sub_level)->first();
            $subscription_level = $package->package;
        } else {
            $subscription_level = "Basic";
        }

        $audio->subscription_level = $subscription_level;

        return $audio;
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

        $audios = Audio::where('status', '>=', 0);
        if(!empty($search)){
            $audios = $audios->where('title', 'like', '%'.$search.'%');
        }
        if($filter !== NULL){
            $audios = $audios->where('status', $filter);
        }
        if(!empty($from)){
            $audios = $audios->where('release_date', '>=', $from);
        }
        if(!empty($to)){
            $audios = $audios->where('release_date', '<=', $to);
        }
        if((($sort_by == 'title') || ($sort_by == 'release_date')) && (($sort == 'asc') || ($sort == 'desc'))){
            $audios = $audios->orderBy($sort_by, $sort);
        }

        if($audios->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Audio was fetched',
                'data' => null
            ], 200);
        }

        $audios = $audios->paginate($limit);
        foreach($audios as $audio){
            $audio = self::audio($audio);
        }

        return response([
            'status' => 'success',
            'message' => 'Audios fetched successfully',
            'data' => $audios
        ], 200);
    }

    public function summary(){
        $total_audio = Audio::count();
        $total_views = OpenedAudio::get()->sum('frequency');
        $popular_audio = Audio::orderBy('favourite_count', 'desc')->orderBy('opened_count', 'desc')->limit(5)->get();

        foreach($popular_audio as $audio){
            $audio = self::audio($audio);
        }

        $data = [
            'total_audio' => number_format($total_audio),
            'total_views' => number_format($total_views),
            'popular_audio' => $popular_audio
        ];

        return response([
            'status' => 'success',
            'message' => 'Audio Summary fetched',
            'data' => $data
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAudioRequest $request)
    {
        $all = $request->except(['audio', 'categories', 'photo']);
        if(!empty($request->audio_file)){
            if(!$upload = FileManagerController::upload_file($request->audio_file, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Audio file could not be uploaded'
                ], 500);
            }
            $all['audio_file'] = $upload->id;
        }
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Photo could not be uploaded'
                ], 500);
            }
            $all['photo'] = $upload->id;
        }
        $all['categories'] = join(',', $request->categories);
        $all['status'] = 1;

        if(!$audio = Audio::create($all)){
            if(isset($all['audio_file']) && !empty($all['audio_file'])){
                FileManagerController::delete($all['audio_file']);
            }
            if(isset($all['photo']) and !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Audio upload failed'
            ], 500);
        }

        $audio = self::audio($audio);

        return response([
            'status' => 'success',
            'message' => 'Audio uploaded successfully',
            'data' => $audio
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Audio $audio)
    {
        $audio = self::audio($audio);

        return response([
            'status' => 'success',
            'message' => 'Audio fetched successfully',
            'data' => $audio
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAudioRequest $request, Audio $audio)
    {
        $all = $request->except(['audio_file', 'categories', 'photo']);
        if(!empty($request->audio_file)){
            if(!$upload = FileManagerController::upload_file($request->audio_file, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Audio file could not be uploaded'
                ], 500);
            }
            $all['audio_file'] = $upload->id;
            $old_audio = $audio->audio_file;
        }
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Photo could not be uploaded'
                ], 500);
            }
            $all['photo'] = $upload->id;
            $old_photo = $audio->photo;
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
        if(!$audio->update($all)){
            if(isset($all['audio_file']) && !empty($all['audio_file'])){
                FileManagerController::delete($all['audio']);
            }
            if(isset($all['photo']) and !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Audio update failed'
            ], 500);
        }
        $audio->update_dependencies();
        if(isset($old_audio)){
            FileManagerController::delete($old_audio);
        }
        if(isset($old_photo) and !empty($old_photo)){
            FileManagerController::delete($old_photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio updated successfully',
            'data' => self::audio($audio)
        ], 200);
    }

    public function activation(Audio $audio){
        $audio->status = ($audio->status == 0) ? 1 : 0;
        $audio->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::audio($audio)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Audio $audio)
    {
        $audio->delete();
        if(!empty($audio->audio_file)){
            FileManagerController::delete($audio->audio_file);
        }
        if(!empty($audio->photo)){
            FileManagerController::delete($audio->photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio successfully deleted'
        ], 200);
    }
}
