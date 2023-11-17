<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ListenAndLearn;
use App\Models\ListenAndLearnAudio;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\AddListenAndLearnAudioRequest;
use App\Http\Requests\Admin\StoreListenAndLearnRequest;
use App\Http\Requests\Admin\UpdateListenAndLearnRequest;

class ListenAndLearnController extends Controller
{
    private $user;
    private $file_disk = 'public';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public static function listen_and_learn(ListenAndLearn $learn) : ListenAndLearn
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
        $audios = ListenAndLearnAudio::where('listen_and_learn_id', $learn->id)->get();
        if(!empty($audios)){
            foreach($audios as $audio){
                $audio->audio = FileManagerController::fetch_file($audio->audio);
            }
        }
        $learn->audios = $audios;

        $sub_level = $learn->subscription_level;
        if($sub_level > 0){
            $package = SubscriptionPackage::where('level', $sub_level)->first();
            $subscription_level = $package->package;
        } else {
            $subscription_level = "Basic";
        }

        $learn->subscription_level = $subscription_level;

        return $learn;
    }

    public static function audio(ListenAndLearnAudio $audio){
        $audio = FileManagerController::fetch_file($audio->audio);

        return $audio;
    }

    public function summary(){
        $learns = ListenAndLearn::orderBy('favourite_count', 'desc')->orderBy('opened_count', 'asc');
        if($learns->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched',
                'data' => null
            ], 200);
        }
        $learns = $learns->limit(3)->get();
        foreach($learns as $learn){
            $learn = $this->listen_and_learn($learn);
        }

        return response([
            'status' => 'success',
            'message' => 'Strategy Summary fetched successfully',
            'data' => $learns
        ], 200);
    }

    public function index(){
        $search = !empty($_GET['search']) ? $_GET['search'] : "";
        $filter = isset($_GET['status']) ? (int)$_GET['status'] : NULL;
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : 'asc';
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $learns = ListenAndLearn::where('status', '>=', 0);
        if(!empty($learns)){
            $learns = $learns->where('title', 'like', '%'.$search.'%');
        }
        if($filter !== NULL){
            $learns = $learns->where('status', $filter);
        }
        $learns->orderBy('title', $sort);

        if($learns->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Listen and Learn Strategy was fetched',
                'data' => null
            ], 200);
        }

        $learns = $learns->paginate($limit);
        foreach($learns as $learn){
            $learn = self::listen_and_learn($learn);
        }

        return response([
            'status' => 'success',
            'message' => 'Listen and Learn Strategies fetched successfully',
            'data' => $learns
        ], 200);
    }

    public function store(StoreListenAndLearnRequest $request){
        $all = $request->except(['photo', 'categories']);
        if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
            return response([
                'status' => 'failed',
                'message' => 'Photo could not be uploaded'
            ], 500);
        }
        $all['photo'] = $upload->id;
        $all['categories'] = join(',', $request->categories);

        if(!$learn = ListenAndLearn::create($all)){
            if(isset($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Listen and Learn Upload Failed'
            ], 500);
        }

        $learn = self::listen_and_learn($learn);

        return response([
            'status' => 'success',
            'message' => 'Listen and Learn Uploaded successfully',
            'data' => $learn
        ], 200);
    }

    public function add_audio(AddListenAndLearnAudioRequest $request, ListenAndLearn $learn){
        if(!$upload = FileManagerController::upload_file($request->audio, env('FILE_DISK', $this->file_disk))){
            return response([
                'status' => 'failed',
                'message' => 'Audio Upload Failed'
            ], 500);
        }

        ListenAndLearnAudio::create([
            'listen_and_learn_id' => $learn->id,
            'audio' => $upload->id
        ]);

        $learn = self::listen_and_learn($learn);

        return response([
            'status' => 'success',
            'message' => 'Listen and Learn Audio uploaded successfully',
            'data' => $learn
        ], 200);
    }

    public function update_audio(AddListenAndLearnAudioRequest $request, ListenAndLearnAudio $audio){
        if(!$upload = FileManagerController::upload_file($request->audio, env('FILE_DISK', $this->file_disk))){
            return response([
                'status' => 'failed',
                'message' => 'Audio Upload Failed'
            ], 500);

            if(!empty($audio->audio)){
                $old_audio = $audio->audio;
            }
        }

        $audio->audio = $upload->id;
        $audio->save();

        if(isset($old_audio)){
            FileManagerController::delete($old_audio);
        }

        return response([
            'status' => 'success',
            'message' => 'Audio updated successfully',
            'data' => self::audio($audio)
        ], 200);
    }

    public function delete_audio(ListenAndLearnAudio $audio){
        $audio->delete();
        FileManagerController::delete($audio->audio);

        $learn = ListenAndLearn::find($audio->listen_and_learn_id);
        $learn = self::listen_and_learn($learn);

        return response([
            'status' => 'success',
            'message' => 'Audioo deleted successfully',
            'data' => $learn
        ], 200);
    }

    public function show(ListenAndLearn $learn){
        $learn = self::listen_and_learn($learn);

        return response([
            'status' => 'success',
            'message' => 'Listen and Learn Strategy fetched successfully',
            'data' => $learn
        ], 200);
    }

    public function update(UpdateListenAndLearnRequest $request, ListenAndLearn $learn){
        $all = $request->except(['photo', 'categories']);
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Photo could not be uploaded'
                ], 500);
            }

            $all['photo'] = $upload->id;
            $old_photo = $learn->photo;
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

        if(!$learn->update($all)){
            if(isset($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Listen and Learn Strategy Update failed'
            ], 500);
        }

        $learn->update_dependencies();

        if(isset($old_photo)){
            FileManagerController::delete($old_photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Listen and Learn Strategy updated successfully',
            'data' => self::listen_and_learn($learn)
        ], 200);
    }

    public function destroy(ListenAndLearn $learn){
        $learn->delete();

        $audios = ListenAndLearnAudio::where('listen_and_learn_id', $learn->id)->get();
        if(!empty($audios)){
            foreach($audios as $audio){
                $audio->delete();
                FileManagerController::delete($audio->audio);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Listen and Learn Startegy deleted successfully'
        ], 200);
    }
}
