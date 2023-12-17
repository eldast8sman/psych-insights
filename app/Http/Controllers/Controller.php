<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Audio;
use App\Models\FavouriteResource;
use App\Models\User;
use App\Models\UserCategoryLog;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public static function log_activity($user_id, $activity, $activity_model=null, $activity_id=null)
    {
        ActivityLog::create([
            'user_id' => $user_id,
            'activity' => $activity,
            'activity_model' => $activity_model,
            'activity_id' => $activity_id
        ]);
    }

    public static function paginate_array($array, $per_page=10, $page=null, $options=[]){
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $page = intval($page);
        $per_page = intval($per_page);
        $items = $array instanceof Collection ? $array : Collection::make($array);
        $results = $items->slice(($page - 1) * $per_page, $per_page)->values();
        return new LengthAwarePaginator($results, $items->count(), $per_page, $page, $options);
    }

    public static function favourite_resource($type, $user_id, $resource_id){
        $resource = FavouriteResource::where('type', $type)->where('user_id', $user_id)->where('resource_id', $resource_id)->first();
        if(empty($resource)){
            FavouriteResource::create([
                'user_id' => $user_id,
                'type' => $type,
                'resource_id' => $resource_id
            ]);

            return "saved";
        } else {
            $resource->delete();
            return "deleted";
        }
    }

    public static function category_log($user_id, $categories=[]) : void
    {
        if(!empty($categories)){
            foreach($categories as $category){
                $usercateg = UserCategoryLog::where('user_id', $user_id)->where('category_id', $category->id)->where('day', date('Y-m-d'));
                if($usercateg->count() < 1){
                    UserCategoryLog::create([
                        'user_id' => $user_id,
                        'category_id' => $category->id,
                        'count' => 1,
                        'day' => date('Y-m-d')
                    ]);
                } else {
                    $usercateg = $usercateg->first();
                    $usercateg->count += 1;
                    $usercateg->save();
                }
            }
        }
    }

    public static function complete_resource($user_id) : void
    {
        $user = User::find($user_id);
        $user->resources_completed += 1;
        $user->save();
    }
}
