<?php

namespace App\Http\Controllers\Admin;

use App\Models\Video;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\StoreVideoRequest;
use App\Http\Requests\Admin\UpdateVideoRequest;

class VideoController extends Controller
{
    private $user;
    private $file_disk = 'public';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public static function video(Video $video) : Video
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

        return $video;
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

        $videos = Video::where('status', '>=', 0);
        if(!empty($search)){
            $videos = $videos->where('title', 'like', '%'.$search.'%');
        }
        if($filter !== NULL){
            $videos = $videos->where('status', $filter);
        }
        if(!empty($from)){
            $videos = $videos->where('release_date', '>=', $from);
        }
        if(!empty($to)){
            $videos = $videos->where('release_date', '<=', $to);
        }
        if((($sort_by == 'title') || ($sort_by == 'release_date')) && (($sort == 'asc') || ($sort == 'desc'))){
            $videos = $videos->orderBy($sort_by, $sort);
        }

        if($videos->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Video was fetched',
                'data' => null
            ], 200);
        }

        $videos = $videos->paginate($limit);
        foreach($videos as $video){
            $video = self::video($video);
        }

        return response([
            'status' => 'success',
            'message' => 'Videos fetched successfully',
            'data' => $videos
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVideoRequest $request)
    {
        $all = $request->except(['video', 'photo', 'categories']);
        if(!empty($request->video)){
            if(!$upload = FileManagerController::upload_file($request->video, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Video file could not be uploaded'
                ], 500);
            }
            $all['video'] = $upload->id;
        }
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                if(isset($all['video']) && !empty($all['video'])){
                    FileManagerController::delete($all['video']);
                }
                return response([
                    'status' => 'failed',
                    'message' => 'Photo could not be uploaded'
                ], 500);
            }
            $all['photo'] = $upload->id;
        }
        $all['categories'] = join(',', $request->categories);

        if(!$video = Video::create($all)){
            if(isset($all['photo']) && !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }
            if(isset($all['video']) && !empty($all['video'])){
                FileManagerController::delete($all['video']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Video upload failed'
            ], 500);
        }

        $video = self::video($video);

        return response([
            'status' => 'success',
            'message' => 'Video uploaded successfully',
            'data' => $video
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Video $video)
    {
        $video = self::video($video);

        return response([
            'status' => 'success',
            'message' => 'Video fetched successfully',
            'data' => $video
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVideoRequest $request, Video $video)
    {
        $old_photo = $video->photo;
        $old_video = $video->video;
        $all = $request->except(['video', 'photo', 'categories']);
        if(!empty($request->video)){
            if(!$upload = FileManagerController::upload_file($request->video, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Video file could not be uploaded'
                ], 500);
            }
            $all['photo'] = $upload->id;
        }
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                if(isset($all['video']) && !empty($all['video'])){
                    FileManagerController::delete($all['video']);
                }
                return response([
                    'status' => 'failed',
                    'message' => 'Photo could not be uploaded'
                ], 500);
            }
            $all['video'] = $upload->id;
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
        if(!$video->update($all)){
            if(isset($all['photo']) && !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }
            if(isset($all['video']) && !empty($all['video'])){
                FileManagerController::delete($all['video']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Video update failed'
            ], 500);
        }
        if(!empty($old_video)){
            FileManagerController::delete($old_video);
        }
        if(!empty($old_photo)){
            FileManagerController::delete($old_photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Video updated successfully',
            'data' => self::video($video)
        ], 200);
    }

    public function activation(Video $video){
        $video->status = ($video->status == 0) ? 1 : 0;
        $video->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::video($video)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Video $video)
    {
        $video->delete();
        if(!empty($video->video)){
            FileManagerController::delete($video->video);
        }
        if(!empty($video->photo)){
            FileManagerController::delete($video->photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Video successfully deleted'
        ], 200);
    }
}
