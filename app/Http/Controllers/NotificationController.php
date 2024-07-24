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

    public function test_payload(){
        $json = '{
    "signedTransactionInfo": "eyJhbGciOiJFUzI1NiIsIng1YyI6WyJNSUlFTURDQ0E3YWdBd0lCQWdJUWZUbGZkMGZOdkZXdnpDMVlJQU5zWGpBS0JnZ3Foa2pPUFFRREF6QjFNVVF3UWdZRFZRUURERHRCY0hCc1pTQlhiM0pzWkhkcFpHVWdSR1YyWld4dmNHVnlJRkpsYkdGMGFXOXVjeUJEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURUxNQWtHQTFVRUN3d0NSell4RXpBUkJnTlZCQW9NQ2tGd2NHeGxJRWx1WXk0eEN6QUpCZ05WQkFZVEFsVlRNQjRYRFRJek1Ea3hNakU1TlRFMU0xb1hEVEkxTVRBeE1URTVOVEUxTWxvd2daSXhRREErQmdOVkJBTU1OMUJ5YjJRZ1JVTkRJRTFoWXlCQmNIQWdVM1J2Y21VZ1lXNWtJR2xVZFc1bGN5QlRkRzl5WlNCU1pXTmxhWEIwSUZOcFoyNXBibWN4TERBcUJnTlZCQXNNSTBGd2NHeGxJRmR2Y214a2QybGtaU0JFWlhabGJHOXdaWElnVW1Wc1lYUnBiMjV6TVJNd0VRWURWUVFLREFwQmNIQnNaU0JKYm1NdU1Rc3dDUVlEVlFRR0V3SlZVekJaTUJNR0J5cUdTTTQ5QWdFR0NDcUdTTTQ5QXdFSEEwSUFCRUZFWWUvSnFUcXlRdi9kdFhrYXVESENTY1YxMjlGWVJWLzB4aUIyNG5DUWt6UWYzYXNISk9OUjVyMFJBMGFMdko0MzJoeTFTWk1vdXZ5ZnBtMjZqWFNqZ2dJSU1JSUNCREFNQmdOVkhSTUJBZjhFQWpBQU1COEdBMVVkSXdRWU1CYUFGRDh2bENOUjAxREptaWc5N2JCODVjK2xrR0taTUhBR0NDc0dBUVVGQndFQkJHUXdZakF0QmdnckJnRUZCUWN3QW9ZaGFIUjBjRG92TDJObGNuUnpMbUZ3Y0d4bExtTnZiUzkzZDJSeVp6WXVaR1Z5TURFR0NDc0dBUVVGQnpBQmhpVm9kSFJ3T2k4dmIyTnpjQzVoY0hCc1pTNWpiMjB2YjJOemNEQXpMWGQzWkhKbk5qQXlNSUlCSGdZRFZSMGdCSUlCRlRDQ0FSRXdnZ0VOQmdvcWhraUc5Mk5rQlFZQk1JSCtNSUhEQmdnckJnRUZCUWNDQWpDQnRneUJzMUpsYkdsaGJtTmxJRzl1SUhSb2FYTWdZMlZ5ZEdsbWFXTmhkR1VnWW5rZ1lXNTVJSEJoY25SNUlHRnpjM1Z0WlhNZ1lXTmpaWEIwWVc1alpTQnZaaUIwYUdVZ2RHaGxiaUJoY0hCc2FXTmhZbXhsSUhOMFlXNWtZWEprSUhSbGNtMXpJR0Z1WkNCamIyNWthWFJwYjI1eklHOW1JSFZ6WlN3Z1kyVnlkR2xtYVdOaGRHVWdjRzlzYVdONUlHRnVaQ0JqWlhKMGFXWnBZMkYwYVc5dUlIQnlZV04wYVdObElITjBZWFJsYldWdWRITXVNRFlHQ0NzR0FRVUZCd0lCRmlwb2RIUndPaTh2ZDNkM0xtRndjR3hsTG1OdmJTOWpaWEowYVdacFkyRjBaV0YxZEdodmNtbDBlUzh3SFFZRFZSME9CQllFRkFNczhQanM2VmhXR1FsekUyWk9FK0dYNE9vL01BNEdBMVVkRHdFQi93UUVBd0lIZ0RBUUJnb3Foa2lHOTJOa0Jnc0JCQUlGQURBS0JnZ3Foa2pPUFFRREF3Tm9BREJsQWpFQTh5Uk5kc2twNTA2REZkUExnaExMSndBdjVKOGhCR0xhSThERXhkY1BYK2FCS2pqTzhlVW85S3BmcGNOWVVZNVlBakFQWG1NWEVaTCtRMDJhZHJtbXNoTnh6M05uS20rb3VRd1U3dkJUbjBMdmxNN3ZwczJZc2xWVGFtUllMNGFTczVrPSIsIk1JSURGakNDQXB5Z0F3SUJBZ0lVSXNHaFJ3cDBjMm52VTRZU3ljYWZQVGp6Yk5jd0NnWUlLb1pJemowRUF3TXdaekViTUJrR0ExVUVBd3dTUVhCd2JHVWdVbTl2ZENCRFFTQXRJRWN6TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd0hoY05NakV3TXpFM01qQXpOekV3V2hjTk16WXdNekU1TURBd01EQXdXakIxTVVRd1FnWURWUVFERER0QmNIQnNaU0JYYjNKc1pIZHBaR1VnUkdWMlpXeHZjR1Z5SUZKbGJHRjBhVzl1Y3lCRFpYSjBhV1pwWTJGMGFXOXVJRUYxZEdodmNtbDBlVEVMTUFrR0ExVUVDd3dDUnpZeEV6QVJCZ05WQkFvTUNrRndjR3hsSUVsdVl5NHhDekFKQmdOVkJBWVRBbFZUTUhZd0VBWUhLb1pJemowQ0FRWUZLNEVFQUNJRFlnQUVic1FLQzk0UHJsV21aWG5YZ3R4emRWSkw4VDBTR1luZ0RSR3BuZ24zTjZQVDhKTUViN0ZEaTRiQm1QaENuWjMvc3E2UEYvY0djS1hXc0w1dk90ZVJoeUo0NXgzQVNQN2NPQithYW85MGZjcHhTdi9FWkZibmlBYk5nWkdoSWhwSW80SDZNSUgzTUJJR0ExVWRFd0VCL3dRSU1BWUJBZjhDQVFBd0h3WURWUjBqQkJnd0ZvQVV1N0Rlb1ZnemlKcWtpcG5ldnIzcnI5ckxKS3N3UmdZSUt3WUJCUVVIQVFFRU9qQTRNRFlHQ0NzR0FRVUZCekFCaGlwb2RIUndPaTh2YjJOemNDNWhjSEJzWlM1amIyMHZiMk56Y0RBekxXRndjR3hsY205dmRHTmhaek13TndZRFZSMGZCREF3TGpBc29DcWdLSVltYUhSMGNEb3ZMMk55YkM1aGNIQnNaUzVqYjIwdllYQndiR1Z5YjI5MFkyRm5NeTVqY213d0hRWURWUjBPQkJZRUZEOHZsQ05SMDFESm1pZzk3YkI4NWMrbGtHS1pNQTRHQTFVZER3RUIvd1FFQXdJQkJqQVFCZ29xaGtpRzkyTmtCZ0lCQkFJRkFEQUtCZ2dxaGtqT1BRUURBd05vQURCbEFqQkFYaFNxNUl5S29nTUNQdHc0OTBCYUI2NzdDYUVHSlh1ZlFCL0VxWkdkNkNTamlDdE9udU1UYlhWWG14eGN4ZmtDTVFEVFNQeGFyWlh2TnJreFUzVGtVTUkzM3l6dkZWVlJUNHd4V0pDOTk0T3NkY1o0K1JHTnNZRHlSNWdtZHIwbkRHZz0iLCJNSUlDUXpDQ0FjbWdBd0lCQWdJSUxjWDhpTkxGUzVVd0NnWUlLb1pJemowRUF3TXdaekViTUJrR0ExVUVBd3dTUVhCd2JHVWdVbTl2ZENCRFFTQXRJRWN6TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd0hoY05NVFF3TkRNd01UZ3hPVEEyV2hjTk16a3dORE13TVRneE9UQTJXakJuTVJzd0dRWURWUVFEREJKQmNIQnNaU0JTYjI5MElFTkJJQzBnUnpNeEpqQWtCZ05WQkFzTUhVRndjR3hsSUVObGNuUnBabWxqWVhScGIyNGdRWFYwYUc5eWFYUjVNUk13RVFZRFZRUUtEQXBCY0hCc1pTQkpibU11TVFzd0NRWURWUVFHRXdKVlV6QjJNQkFHQnlxR1NNNDlBZ0VHQlN1QkJBQWlBMklBQkpqcEx6MUFjcVR0a3lKeWdSTWMzUkNWOGNXalRuSGNGQmJaRHVXbUJTcDNaSHRmVGpqVHV4eEV0WC8xSDdZeVlsM0o2WVJiVHpCUEVWb0EvVmhZREtYMUR5eE5CMGNUZGRxWGw1ZHZNVnp0SzUxN0lEdll1VlRaWHBta09sRUtNYU5DTUVBd0hRWURWUjBPQkJZRUZMdXczcUZZTTRpYXBJcVozcjY5NjYvYXl5U3JNQThHQTFVZEV3RUIvd1FGTUFNQkFmOHdEZ1lEVlIwUEFRSC9CQVFEQWdFR01Bb0dDQ3FHU000OUJBTURBMmdBTUdVQ01RQ0Q2Y0hFRmw0YVhUUVkyZTN2OUd3T0FFWkx1Tit5UmhIRkQvM21lb3locG12T3dnUFVuUFdUeG5TNGF0K3FJeFVDTUcxbWloREsxQTNVVDgyTlF6NjBpbU9sTTI3amJkb1h0MlFmeUZNbStZaGlkRGtMRjF2TFVhZ002QmdENTZLeUtBPT0iXX0.eyJ0cmFuc2FjdGlvbklkIjoiMjAwMDAwMDY1OTA2NTcxOCIsIm9yaWdpbmFsVHJhbnNhY3Rpb25JZCI6IjIwMDAwMDA2NTkwNjU3MTgiLCJidW5kbGVJZCI6ImNvbS5wc3ljaGluc2lnaHQucHN5Y2hpbnNpZ2h0cyIsInByb2R1Y3RJZCI6IjAwMDUiLCJwdXJjaGFzZURhdGUiOjE3MjEyMDkwOTcwMDAsIm9yaWdpbmFsUHVyY2hhc2VEYXRlIjoxNzIxMjA5MDk3MDAwLCJxdWFudGl0eSI6MSwidHlwZSI6Ik5vbi1SZW5ld2luZyBTdWJzY3JpcHRpb24iLCJpbkFwcE93bmVyc2hpcFR5cGUiOiJQVVJDSEFTRUQiLCJzaWduZWREYXRlIjoxNzIxMjA5MTEwNjkyLCJlbnZpcm9ubWVudCI6IlNhbmRib3giLCJ0cmFuc2FjdGlvblJlYXNvbiI6IlBVUkNIQVNFIiwic3RvcmVmcm9udCI6Ik5HQSIsInN0b3JlZnJvbnRJZCI6IjE0MzU2MSIsInByaWNlIjoxNDkwMDAwMCwiY3VycmVuY3kiOiJOR04ifQ.x3r9DZX5zPAs70HfliWVBghSnrEV7w5hw_1DrdaCRSvU0UIoiZ573tOKUA3TivO_xH9CX_hSNTgZjbPovfSsQw"
}';

        $object = json_decode($json);
        $array = explode('.', $object->signedTransactionInfo);
        $data = [];
        for($i=0; $i<=1; $i++){
            $data[] = [json_decode(base64_decode($array[$i]), true)];
        }
        return response([
            'data' => $data
        ]);
    }
}