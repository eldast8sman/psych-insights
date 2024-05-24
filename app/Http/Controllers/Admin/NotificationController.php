<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\UpdateNotificationSettingRequest;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\NotificationSetting;
use App\Models\CurrentSubscription;
use App\Models\User;
use App\Models\UserDeactivation;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private $user;
    private $file_disk = 's3';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function fetch_setting(){
        $setting = NotificationSetting::where('admin_id', $this->user->id)->first();
        if(empty($setting)){
            return response([
                'status' => 'failed',
                'message' => 'No Notification Setting was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Notification Setting fetched successfully',
            'data' => $setting
        ], 200);
    }

    public function update_setting(UpdateNotificationSettingRequest $request){
        $setting = NotificationSetting::where('admin_id', $this->user->id)->first();
        $all = $request->all();
        if(empty($setting)){
            $all['admin_id'] = $this->user->id;
            $setting = NotificationSetting::create($all);
        } else {
            $setting->update($all);
        }

        return response([
            'status' => 'auccess',
            'message' => 'Notification Setting successfully updated',
            'data' => $setting
        ], 200);

    }

    public function notification_count(){
        $not_count = AdminNotification::where('admin_id', $this->user->id)->where('status', 1)->where('opened', 0)->count();

        return response([
            'status' => 'success',
            'message' => 'Notification count fetched',
            'data' => [
                'notification_count' => $not_count
            ]
        ]);
    }

    public function index(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $notifications = AdminNotification::where('status', 1)->where('admin_id', $this->user->id)->paginate($limit);
        foreach($notifications as $notification){
            $url = "";
            if($notification->page == 'user'){
                $user = User::find($notification->identifier);
                if(!empty($user) and !empty($user->profile_photo)){
                    $url = FileManagerController::fetch_file($user->profile_photo)->url;
                }
            } elseif($notification->page == 'deactivated_users'){
                $d_user = UserDeactivation::find($notification->identifier);
                $user = User::find($d_user->user_id);
                if(!empty($user->profile_photo)){
                    $url = FileManagerController::fetch_file($user->profile_photo)->url;
                }
            } elseif($notification->page == 'subscribers'){
                $current = CurrentSubscription::find($notification->identifier);
                $user = User::find($current->user_id);
                if(!empty($user->profile_photo)){
                    $url = $user->profile_photo;
                }            
            }
            $notification->image_url = $url;
        }

        return response([
            'status' => 'success',
            'message' => 'Notifications fetched successfully',
            'data' => $notifications
        ], 200);
    }

    public function mark_as_read(AdminNotification $notification){
        if($notification->admin_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Notification was fetched'
            ]);
        }
        $notification->opened = 1;
        $notification->save();

        return response([
            'status' => 'success',
            'message' => 'Notification successfully opened'
        ], 200);
    }

    public function mark_all_as_read(){
        $notifications = AdminNotification::where('admin_id', $this->user->id)->where('status', 1)->where('opened', 0);
        if($notifications->count() > 0){
            foreach($notifications->get() as $notification){
                $notification->opened = 1;
                $notification->save();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ], 200);
    }

    public function destroy(AdminNotification $notification){
        if($notification->admin_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Notification was fetched'
            ]);
        }

        $notification->status = 0;
        $notification->save();

        return response([
            'status' => 'success',
            'message' => 'Notification successfully cancelled'
        ], 200);
    }
}
