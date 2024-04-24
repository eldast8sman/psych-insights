<?php

namespace App\Http\Controllers;

use App\Jobs\SubscriptionAutoRenewal;
use App\Models\CurrentSubscription;
use App\Models\GoalCategory;
use App\Models\SubscriptionHistory;
use App\Models\User;
use App\Models\UserGoalReminder;
use App\Services\PushNotificationService;
use Exception;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public $errors = "";

    public function send_notification($device_token, $title, $body, $data=[]){
        try {
            $push = new PushNotificationService();
            $push->sendPushNotification($device_token, $title, $body, $data);

            return true;
        } catch(Exception $e){
            $this->errors = $e->getMessage();
            return false;
        }
    }

    public function to_send(){
        $present = date('Y-m-d H:i:s');
        $next_hours = date('Y-m-d H:i:s', time() + (60 * 60 * 24));

        $to_sends = UserGoalReminder::where('next_reminder', '>=', $present)->where('next_reminder', '<=', $next_hours)->where('status', 1);
        if($to_sends->count() > 0){
            $messages = [];
            foreach($to_sends->get() as $to_send){
                $user = User::find($to_send->user_id);
                $goal = GoalCategory::find($to_send->goal_category_id);
                $data = [
                    'reminder_time' => $to_send->next_reminder
                ];

                if(!empty($user->device_token)){
                    if($this->send_notification($user->device_token, $goal->category, $to_send->reminder, $data)){
                        $messages[] = "Notification Sent";
                        if($to_send->reminder_type == 'recurring'){
                            $to_send->next_reminder = date('Y-m-d H:i:s', strtotime($to_send->next_reminder) + (60 * 60 * 24 * 7));
                            $to_send->save();
                        } else {
                            $to_send->status = 0;
                            $to_send->save();
                        }
                    } else {
                        $messages[] = $this->errors;
                    }
                }
                if(!empty($user->web_token)){
                    if($this->send_notification($user->web_token, $goal->category, $to_send->reminder, $data)){
                        $messages[] = "Notification Sent";
                        if($to_send->reminder_type == 'recurring'){
                            $to_send->next_reminder = date('Y-m-d H:i:s', strtotime($to_send->next_reminder) + (60 * 60 * 24 * 7));
                            $to_send->save();
                        } else {
                            $to_send->status = 0;
                            $to_send->save();
                        }
                    } else {
                        $messages[] = $this->errors;
                    }
                }
            }

            return response([
                'status' => 'success',
                'message' => $messages
            ], 200);
        } else {
            return response([
                'status' => 'success',
                'message' => 'No Notifications for today'
            ]);
        }
    }

    public static function check_subscriptions(){
        $expired_histories = SubscriptionHistory::where('grace_end', '<', date('Y-m-d'))->where('status', 1);
        if($expired_histories->count() > 0){
            foreach($expired_histories->get() as $history){
                $history->status = 2;
                $history->save();
            }
        }

        $expired_currents = CurrentSubscription::where('grace_end', '<=', date('Y-m-d'))->where('status', 1);
        if($expired_currents->count() > 0){
            foreach($expired_currents->get() as $expired){
                SubscriptionAutoRenewal::dispatch($expired->id, "expired");
            }
        }

        $to_renews = CurrentSubscription::where('end_date', '<=', date('Y-m-d'))->where('grace_end', '>=', date('Y-m-d'))->where('status', 1);
        if($to_renews->count() > 0){
            foreach($to_renews as $renew){
                SubscriptionAutoRenewal::dispatch($renew->id, "renew");
            }
        }
    }
}
