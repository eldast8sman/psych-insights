<?php

namespace App\Jobs;

use App\Http\Controllers\Admin\NotificationController;
use App\Models\GoalCategory;
use App\Models\User;
use App\Models\UserGoalReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $to_send = UserGoalReminder::find($this->id);
        $user = User::find($to_send->user_id);
        $goal = GoalCategory::find($to_send->goal_category_id);
        $data = [
            'reminder_time' => $to_send->next_reminder
        ];

        $not = new NotificationController();
        if(!empty($user->device_token)){
            if($not->send_notification($user->device_token, $goal->category, $to_send->reminder, $data)){
                $messages[] = "Notification Sent";
                if($to_send->reminder_type == 'recurring'){
                    $to_send->next_reminder = date('Y-m-d H:i:s', strtotime($to_send->next_reminder) + (60 * 60 * 24 * 7));
                    $to_send->save();
                } else {
                    $to_send->status = 0;
                    $to_send->save();
                }
            }
        }
        if(!empty($user->web_token)){
            if($not->send_notification($user->web_token, $goal->category, $to_send->reminder, $data)){
                $messages[] = "Notification Sent";
                if($to_send->reminder_type == 'recurring'){
                    $to_send->next_reminder = date('Y-m-d H:i:s', strtotime($to_send->next_reminder) + (60 * 60 * 24 * 7));
                    $to_send->save();
                } else {
                    $to_send->status = 0;
                    $to_send->save();
                }
            }
        }
    }
}
