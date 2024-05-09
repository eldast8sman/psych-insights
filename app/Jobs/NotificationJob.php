<?php

namespace App\Jobs;

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\NotificationController as ControllersNotificationController;
use App\Models\CurrentSubscription;
use App\Models\GoalCategory;
use App\Models\Notification;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserGoalReminder;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;
    public $type;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $type)
    {
        $this->id = $user_id;
        $this->type = $type;
    }

    public function send_inactive_notification(){
        $user = User::find($this->id);
        if(!empty($user->device_token) or !empty($user->web_token)){
            $date1 = new DateTime(date('Y-m-d', strtotime($user->last_login)));
            $date2 = new DateTime(date('Y-m-d'));

            $interval = $date1->diff($date2);

            $diff = $interval->format('%a days');

            $not = new ControllersNotificationController();
            if(!empty($user->device_token)){
                $not->send_notification($user->device_token, 'Inactivity', 'Hey, You have been inactive for '.$diff.' Hope all is well?');
            }
            if(!empty($user->web_token)){
                $not->send_notification($user->web_token, 'Inactivity', 'Hey, You have been inactive for '.$diff.' Hope all is well?');
            }
        }
    }

    public function assessment_reminder(){
        $user = User::find($this->id);
        if(!empty($user->device_token) or !empty($user->web_token)){
            $current = CurrentSubscription::where('user_id', $user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->orderBy('grace_end', 'desc')->first();
            if(!empty($current)){
                $package = SubscriptionPackage::find($current->subscription_package_id);
                if($package->free_trial != 1){
                    $type = "Dass21 Assessment";
                } else {
                    $type = "K10 Assessment";
                }
            } else {
                $type = "K14 Assessment";
            }
            
            $title = "Assessment Reminder";
            $message = "This is to remind you that you are to partake in the {$type} today";
            
            $not = new ControllersNotificationController();
            if(!empty($user->device_token)){
                $not->send_notification($user->device_token, $title, $message);
            }
            if(!empty($user->web_token)){
                $not->send_notification($user->web_token, $title, $message);
            }

            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $message,
                'model' => 'daily_questions',
                'read' => 0,
                'status' => 1
            ]);
        }
    }

    public function notification_test(){
        $not = new ControllersNotificationController();
        $token = 'cd8nblkbR-CQFR04p-bMAT:APA91bGvrtEhlF8lwKDcjntArpDJ-RoU4gqoJbqbHT6LBml7CxdwP-Tp11qXqQMUgFDmmPrqZvEVOmV7EqTf2sK6XGss9of93u-WVv8kT6Yeu1VsTwdj-dwpyW14UcAY_BE1ypaHmSvV';
        $not->send_notification($token, "Test", "Notification Test");
    }

    public function daily_question_reminder(){
        $user = User::find($this->id);
        if(!empty($user->device_token) or !empty($user->web_token)){
            $title = "Daily Assessment";
            $body = "You are yet to check in today. Please login to the App to answer your daily assessment Questions";

            $not = new ControllersNotificationController();
            if(!empty($user->device_token)){
                $not->send_notification($user->device_token, $title, $body);
            } else {
                $not->send_notification($user->web_token, $title, $body);
            }

            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'model' => 'daily_questions',
                'read' => 0,
                'status' => 1
            ]);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->type == 'inactive'){
            $this->send_inactive_notification();
        } elseif($this->type == 'next_assessment'){
            $this->assessment_reminder();
        } elseif($this->type == 'next_daily_question'){
            $this->daily_question_reminder();
        } elseif($this->type == 'test'){
            $this->notification_test();
        }
    }
}
