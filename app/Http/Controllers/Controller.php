<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
}
