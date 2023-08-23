<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
}
