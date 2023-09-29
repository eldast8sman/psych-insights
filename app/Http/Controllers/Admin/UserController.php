<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CurrentSubscription;
use App\Models\SubscriptionPackage;

class UserController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    } 
    
    public function summary(){
        $total_count = User::count();
        $premium_count = CurrentSubscription::where('status', 1)->where('grace_end', '>=', date('Y-m-d'))->count();
        $basic_count = $total_count - $premium_count;
        $deactivated_count = User::where('status', 0)->count();

        return response([
            'status' => 'success',
            'message' => 'Summary fetched successfully',
            'data' => [
                'total_users' => $total_count,
                'premium_users' => $premium_count,
                'basic_users' => $basic_count,
                'deactivated_users' => $deactivated_count
            ]
        ], 200);
    }

    public Function index(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $users = User::orderBy('name', 'asc');
        if(!empty($search)){
            $users = $users->where('name', 'like', '%'.$search.'%');
        }

        if($users->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No User was fetched',
                'data' => []
            ], 200);
        }

        $users = $users->paginate($limit);

        foreach($users as $user){
            $current_sub = CurrentSubscription::where('user_id', $user->id)->first();
            $user->start_date = date('Y-m-d', strtotime($user->created_at));
            $user->end_date = date('Y-m-d');
            $user->subscription_package = 'Basic';
            if(!empty($current_sub)){
                if(($current_sub->status == 1) and ($current_sub->grace_end >= date('Y-m-d'))){
                    $subscription = SubscriptionPackage::find($current_sub->subscription_package_id);
                    $user->subscription_package = $subscription->package;
                    $user->start_date = $current_sub->start_date;
                    $user->end_date = $current_sub->end_date;
                } else {
                    $user->end_date = date('Y-m-d', strtotime($current_sub->end_date) + (60 * 60 * 24));
                }
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Users fetched successfully',
            'data' => $users
        ], 200);
    }

    public function show(User $user){
        $current_sub = CurrentSubscription::where('user_id', $user->id)->first();
        $user->start_date = date('Y-m-d', strtotime($user->created_at));
        $user->end_date = date('Y-m-d');
        $user->subscription_package = 'Basic';
        if(!empty($current_sub)){
            if(($current_sub->status == 1) and ($current_sub->grace_end >= date('Y-m-d'))){
                $subscription = SubscriptionPackage::find($current_sub->subscription_package_id);
                $user->subscription_package = $subscription->package;
                $user->start_date = $current_sub->start_date;
                $user->end_date = $current_sub->end_date;
            } else {
                $user->end_date = date('Y-m-d', strtotime($current_sub->end_date) + (60 * 60 * 24));
            }
        }
        $user->recent_activity = ActivityLog::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();

        return response([
            'status' => 'success',
            'message' => 'User fetched successfully',
            'data' => $user
        ], 200);
    }

    public function user_activation(User $user){
        $user->status = ($user->status == 1) ? 0 : 1;
        $user->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful'
        ], 200);
    }
}
