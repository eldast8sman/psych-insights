<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class UserNotificationController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $notifications = Notification::where('user_id', $this->user->id)->where('status', 1)->orderBy('created_at', 'desc');
        
        return response([
            'status' => 'success',
            'message' => 'Notifications fetched successfully',
            'data' => $notifications->paginate($limit)
        ], 200);
    }

    public function notification_count(){
        $counted = Notification::where('user_id', $this->user->id)->where('read', 0)->count();

        return response([
            'status' => 'success',
            'message' => 'Unread Notifications counted successfully',
            'data' => [
                'count' => $counted
            ]
        ], 200);
    }

    public function mark_as_read(Notification $notification){
        $notification->read = 1;
        $notification->save();

        return response([
            'status' => 'success',
            'message' => 'Notification successfully read'
        ], 200);
    }

    public function mark_all_as_read(){
        $notifications = Notification::where('user_id', $this->user->id)->where('status', 1)->where('read', 0);
        if($notifications->count() > 0){
            foreach($notifications->get() as $notification){
                $notification->read = 1;
                $notification->save();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ], 200);
    }

    public function cancel(Notification $notification){
        $notification->status = 0;
        $notification->save();

        return response([
            'status' => 'success',
            'message' => 'Notification successfully cancelled'
        ], 200);
    }

    public function test_notification(){
        $title = "Psych Insights";
        $body = "Push Notification Test";
        
        if(!empty($this->user->device_token)){
            $not = new NotificationController();
            $not->send_notification($this->user->device_token, $title, $body);

            Notification::create([
                'user_id' => $this->user->id,
                'title' => $title,
                'body' => $body,
                'model' => 'test_notification',
                'read' => 0,
                'status' => 1
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'Notification sent'
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Notification::factory(5)->create();
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Notification $notification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification)
    {
        //
    }
}
