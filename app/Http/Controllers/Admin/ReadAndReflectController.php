<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ReadAndReflect;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Models\ReadAndReflectReflection;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\StoreReadAndReflectRequest;
use App\Http\Requests\Admin\StoreReflectionRequest;
use App\Http\Requests\Admin\UpdateReadAndReflectRequest;

class ReadAndReflectController extends Controller
{
    private $user;
    private $file_disk = 'public';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function read_and_reflect(ReadAndReflect $reflection) : ReadAndReflect
    {
        if(!empty($reflection->photo)){
            $reflection->photo = FileManagerController::fetch_file($reflection->photo);
        }
        if(!empty($reflection->categories)){
            $categories = [];

            $categs = explode(',', $reflection->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $reflection->categories = $categories;
        }
        $reflection->reflections = ReadAndReflectReflection::where('read_and_reflect_id', $reflection->id)->get();

        $sub_level = $reflection->subscription_level;
        if($sub_level > 0){
            $package = SubscriptionPackage::where('level', $sub_level)->first();
            $subscription_level = $package->package;
        } else {
            $subscription_level = "Basic";
        }

        $reflection->subscription_level = $subscription_level;

        return $reflection;
    }

    public function summary(){
        $reads = ReadAndReflect::orderBy('favourite_count', 'desc')->orderBy('opened_count', 'asc');
        if($reads->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched',
                'data' => null
            ], 200);
        }
        $reads = $reads->limit(3)->get();
        foreach($reads as $read){
            $read = $this->read_and_reflect($read);
        }

        return response([
            'status' => 'success',
            'message' => 'Strategy Summary fetched successfully',
            'data' => $reads
        ], 200);
    }

    public function index(){
        $search = !empty($_GET['search']) ? $_GET['search'] : "";
        $filter = isset($_GET['status']) ? (int)$_GET['status'] : NULL;
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : 'asc';
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $reflections = ReadAndReflect::where('status', '>=', 0);
        if(!empty($search)){
            $reflections = $reflections->where('title', 'like', '%'.$search.'%');
        }
        if($filter !== NULL){
            $reflections = $reflections->where('status', $filter);
        }
        if($filter !== 0){
            $reflections = $reflections->where('status', '!=', 0);
        }
        $reflections->orderBy('title', $sort);

        if($reflections->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Reflection was fetched',
                'data' => null
            ], 200);
        }

        $reflections = $reflections->paginate($limit);
        foreach($reflections as $reflection){
            $reflection = $this->read_and_reflect($reflection);
        }

        return response([
            'status' => 'success',
            'message' => 'Read and Reflect Strategies fetched successfully',
            'data' => $reflections
        ], 200);
    }

    public function store(StoreReadAndReflectRequest $request){
        $all = $request->except(['photo', 'categories', 'refletions']);
        if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
            return response([
                'status' => 'failed',
                'message' => 'Photo could not be uploaded'
            ], 500);
        }
        $all['photo'] = $upload->id;
        $all['categories'] = join(',', $request->categories);

        if(!$reflection = ReadAndReflect::create($all)){
            if(isset($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Read and Reflect Upload Failed'
            ], 500);
        }

        foreach($request->reflections as $reflect){
            ReadAndReflectReflection::create([
                'read_and_reflect_id' => $reflection->id,
                'reflection' => $reflect
            ]);
        }

        $reflection = $this->read_and_reflect($reflection);

        return response([
            'status' => 'success',
            'message' => 'Read nd Reflect Strategy fetched successfully',
            'data' => $reflection
        ], 200);
    }

    public function show(ReadAndReflect $reflection){
        $reflection = $this->read_and_reflect($reflection);

        return response([
            'status' => 'success',
            'message' => 'Read and Reflect Strategy fetched successfully',
            'data' => $reflection
        ], 200);
    }

    public function add_reflection(StoreReflectionRequest $request, ReadAndReflect $reflection){
        ReadAndReflectReflection::create([
            'read_and_reflect_id' => $reflection->id,
            'reflection' => $request->reflection
        ]);

        $reflection = $this->read_and_reflect($reflection);

        return response([
            'status' => 'success',
            'message' => 'Reflection added to Read and Reflect Strategy',
            'data' => $reflection
        ], 200);
    }

    public function update_reflection(StoreReflectionRequest $request, ReadAndReflectReflection $reflection){
        if(!$reflection->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Reflection update failed'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Reflection updated successfully',
            'data' => $reflection
        ], 200);
    }

    public function delete_reflection(ReadAndReflectReflection $reflection){
        $reflection->delete();

        $reflect = ReadAndReflect::find($reflection->read_and_reflect_id);
        $reflect = $this->read_and_reflect($reflect);

        return response([
            'status' => 'success',
            'message' => 'Reflection Deleted successfully',
            'data' => $reflect
        ], 200);
    }

    public function update(UpdateReadAndReflectRequest $request, ReadAndReflect $reflection){
        $all = $request->except(['photo', 'categories']);
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Photo could not be uploaded'
                ], 500);
            }

            $all['photo'] = $upload->id;
            $old_photo = $reflection->photo;
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

        if(!$reflection->update($all)){
            if(isset($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Read and Reflect Strategy Update failed'
            ], 500);
        }

        $reflection->update_dependencies();

        if(isset($old_photo)){
            FileManagerController::delete($old_photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Read and Reflect Strategy updated successfully',
            'data' => $this->read_and_reflect($reflection)
        ], 200);
    }

    public function destroy(ReadAndReflect $reflection){
        $reflection->status = 0;
        $reflection->save();

        return response([
            'status' => 'success',
            'message' => 'Read and Reflect Strategy deleted successfully'
        ], 200);
    }
}
