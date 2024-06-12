<?php

namespace App\Http\Controllers;

use App\Jobs\NotificationJob;
use App\Jobs\SubscriptionAutoRenewal;
use App\Models\CurrentSubscription;
use App\Models\GoalCategory;
use App\Models\PushNotificationLog;
use App\Models\SubscriptionHistory;
use App\Models\User;
use App\Models\UserGoalReminder;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use PDO;

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

    public function test_notification(){
        NotificationJob::dispatch(1, 'test');
    }

    public static function check_inactivity(){
        $timezones = self::pluck_timezones();

        if(!empty($timezones)){
            foreach($timezones as $timezone){
                $time = Carbon::now($timezone);
                $fifteen_days = date('Y-m-d', time() - (60 * 60 * 24 * 15));
                $users = User::where('last_timezone', $timezone)->where('last_login', '<', $fifteen_days.' 00:00:00');
                if($users->count() < 0){
                    foreach($users->get() as $user){
                        $log = PushNotificationLog::where('user_id', $user->id)->where('notification_type', 'inactive')->first();
                        if(empty($log) or ($log->next_notification <= $time->format('Y-m-d H:i:s'))){
                            NotificationJob::dispatch($user->id, 'inactive');
                            self::update_notification_log($user->id, 'inactive', $time);
                        }
                    }
                }   
            }
        }
        // $fifteen_days = date('Y-m-d', time() - (60 * 60 * 24 * 15));
        // $users = User::where('last_login', '<', $fifteen_days.' 00:00:00');
        // if($users->count() < 0){
        //     foreach($users->get() as $user){
        //         NotificationJob::dispatch($user->id, 'inactive');
        //     }
        // }
    }

    public static function assessment_reminder(){
        $timezones = self::pluck_timezones();

        if(!empty($timezones)){
            foreach($timezones as $timezone){
                $time = Carbon::now($timezone);
                $today = $time->format('Y-m-d');
                $users = User::where('last_timezone', $timezone)->where('next_assessment', '<=', $today);
                if($users->count() > 0){
                    foreach($users->get() as $user){
                        $log = PushNotificationLog::where('user_id', $user->id)->where('notification_type', 'next_assessment')->first();
                        if(empty($log) or ($log->next_notification <= $time->format('Y-m-d H:i:s'))){
                            NotificationJob::dispatch($user->id, 'next_assessment');
                            self::update_notification_log($user->id, 'next_assessment', $time);
                        }
                    }
                }
            }
        }

        // $users = User::where('next_assessment', '<=', date('Y-m-d'));
        // if($users->count() > 0){
        //     foreach($users->get() as $user){
        //         NotificationJob::dispatch($user->id, 'next_assessment');
        //     }
        // }
    }

    public static function daily_reminder(){
        $timezones = self::pluck_timezones();

        if(!empty($timezones)){
            foreach($timezones as $timezone){
                $time = Carbon::now($timezone);
                $today = $time->format('Y-m-d');
                $users = User::where('last_timezone', $timezone)->where('next_daily_question', '<=', $today);
                if($users->count() > 0){
                    foreach($users->get() as $user){
                        $log = PushNotificationLog::where('user_id', $user->id)->where('notification_type', 'daily_reminder')->first();
                        if(empty($log) or ($log->next_notification <= $time->format('Y-m-d H:i:s'))){
                            NotificationJob::dispatch($user->id, 'next_daily_question');
                            self::update_notification_log($user->id, 'daily_reminder', $time);
                        }
                    }
                }
            }
        }

        // $users = User::where('next_daily_question', '<=', date('Y-m-d'));
        // if($users->count() > 0){
        //     foreach($users->get() as $user){
        //         NotificationJob::dispatch($user->id, 'next_daily_question');
        //     }
        // }
    }

    public static function update_notification_log($user_id, $notification_type, Carbon $time){
        $log = PushNotificationLog::where('user_id', $user_id)->where('notification_type', $notification_type)->first();
        if(empty($log)){
            PushNotificationLog::create([
                'user_id' => $user_id,
                'notification_type' => $notification_type,
                'last_notification' => $time->format('Y-m-d H:i:s'),
                'next_notification' => $time->addHours(12)->format('Y-m-d H:i:s')
            ]);
        } else {
            $log->last_notification = $time->format('Y-m-d H:i:s');
            $log->next_notification = $time->addHours(12)->format('Y-m-d H:i:s');
            $log->save();
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
        $timezones = self::pluck_timezones();

        if(!empty($timezones)){
            foreach($timezones as $timezone){
                $time = Carbon::now($timezone);
                $today = $time->format('Y-m-d');

                $expired_histories = SubscriptionHistory::join('users', 'subscription_histories.user_id', '=', 'users.id')
                                    ->where('users.last_timezone', $timezone)
                                    ->where('subscription_histories.grace_end', '<', $today)
                                    ->where('subscription_histories.status', 1)
                                    ->select('subscription_histories.*');
                if($expired_histories->count() > 0){
                    foreach($expired_histories->get() as $history){
                        $history->status = 2;
                        $history->save();
                    }
                }



                $expired_currents = CurrentSubscription::join('users', 'current_subscriptions.user_id', '=', 'users.id')
                                ->where('users.last_timezone', $timezone)
                                ->where('current_subscriptions.grace_end', '<=', $today)
                                ->where('current_subscriptions.status', 1)
                                ->select('current_subscriptions.*');
                if($expired_currents->count() > 0){
                    foreach($expired_currents->get() as $expired){
                        SubscriptionAutoRenewal::dispatch($expired->id, "expired");
                    }
                }

                $to_renews = CurrentSubscription::join('users', 'current_subscriptions.user_id', '=', 'users.id')
                            ->where('users.last_timezone', $timezone)
                            ->where('current_subscriptions.end_date', '<=', $today)
                            ->where('current_subscriptions.grace_end', '>=', $today)
                            ->where('current_subscriptions.status', 1)
                            ->select('current_subscriptions.*');
                if($to_renews->count() > 0){
                    foreach($to_renews as $renew){
                        SubscriptionAutoRenewal::dispatch($renew->id, "renew");
                    }
                }
            }           
        }
        // $expired_histories = SubscriptionHistory::where('grace_end', '<', date('Y-m-d'))->where('status', 1);
        // if($expired_histories->count() > 0){
        //     foreach($expired_histories->get() as $history){
        //         $history->status = 2;
        //         $history->save();
        //     }
        // }

        // $expired_currents = CurrentSubscription::where('grace_end', '<=', date('Y-m-d'))->where('status', 1);
        // if($expired_currents->count() > 0){
        //     foreach($expired_currents->get() as $expired){
        //         SubscriptionAutoRenewal::dispatch($expired->id, "expired");
        //     }
        // }

        // $to_renews = CurrentSubscription::where('end_date', '<=', date('Y-m-d'))->where('grace_end', '>=', date('Y-m-d'))->where('status', 1);
        // if($to_renews->count() > 0){
        //     foreach($to_renews as $renew){
        //         SubscriptionAutoRenewal::dispatch($renew->id, "renew");
        //     }
        // }
    }

    public static function pluck_timezones(){
        $timezones = User::distinct()->pluck('last_timezone')->toArray();
        $array = [];
        foreach($timezones as $timezone){
            $time = Carbon::now($timezone);
            $hour = intval($time->format('H'));
            if((($hour >= 9) and ($hour <= 11)) or (($hour >= 21) and ($hour <= 23))){
                $array[] = $timezone;
            }
        }

        return $array;
    }
}
