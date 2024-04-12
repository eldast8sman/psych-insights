<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\OpenedAudio;
use App\Models\UserCategoryLog;
use App\Models\UserGoalMilestone;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public function activities(){
        $activities = [];

        for($i=6; $i>=0; $i--){
            $date = date('Y-m-d', time() - (60 * 60 * 24 * $i));
            $from = $date." 00:00:00";
            $to = $date." 23:59:59";
            $day = date('l', time() - (60 * 60 * 24 * $i));

            $activities[] = [
                'day' => $day,
                'activities' => ActivityLog::where('user_id', $this->user->id)->where('created_at', '>=', $from)->where('created_at', '<=', $to)->count()
            ];
        }

        return response([
            'status' => 'success',
            'message' => 'Activities fetched successfully',
            'data' => $activities
        ], 200);
    }

    public function my_stat(){
        return response([
            'insightful_days' => $this->user->total_logins,
            'total_checkins' => ActivityLog::where('user_id', $this->user->id)->where('activity', 'checkin')->count(),
            'total_audios' => OpenedAudio::where('user_id', $this->user->id)->count(),
            'longest_streak' => $this->user->longest_streak
        ], 200);
    }

    public function my_progress(){
        $progress = [];

        $categories = Category::orderBy('category', 'asc')->get();
        if(!empty($categories)){
            foreach($categories as $category){
                $logs = [];
                for($i=6; $i>=0; $i--){
                    $date = date('Y-m-d', time() - (60 * 60 * 24 * $i));
                    $day = date('l', time() - (60 * 60 * 24 * $i));
    
                    $log = UserCategoryLog::where('user_id', $this->user->id)->where('category_id', $category->id)->where('day', $date)->first();
                    if(!empty($log)){
                        $count = $log->count;
                    } else {
                        $count = 0;
                    }

                    $logs[] = [
                        'day' => $day,
                        'count' => $count
                    ];
                }

                $progress[] = [
                    'category' => $category->category,
                    'logs' => $logs
                ];
            }
        }

        return response([
            'status' => 'success',
            'message' => 'My Progress fetched successfully',
            'data' => $progress
        ], 200);
    }

    public function days_in_a_row(){
        $activities = [];

        for($i=6; $i>=0; $i--){
            $date = date('Y-m-d', time() - (60 * 60 * 24 * $i));
            $from = $date." 00:00:00";
            $to = $date." 23:59:59";
            $day = date('l', time() - (60 * 60 * 24 * $i));

            $activities[] = [
                'day' => $day,
                'active' => (ActivityLog::where('user_id', $this->user->id)->where('created_at', '>=', $from)->where('created_at', '<=', $to)->count() > 0) ? true : false 
            ];
        }

        return response([
            'status' => 'success',
            'message' => 'Activities fetched successfully',
            'data' => $activities
        ], 200);
    }

    public function milestones(){
        $data = [
            'three_consecutive_days' => ($this->user->longest_streak >= 3) ? true : false,
            'three_goals_completion' => ($this->user->goals_completed >= 3) ? true : false,
            'three_resources_opened' => ($this->user->resources_completed >= 3) ? true : false,
            'completed_health_goal' => (UserGoalMilestone::where('user_id', $this->user->id)->where('goal_category', 'like', '%health%')->count() > 0) ? true : false,
            'completed_work_goal' => (UserGoalMilestone::where('user_id', $this->user->id)->where('goal_category', 'like', '%work%')->count() > 0) ? true : false,
            'completed_relationship_goal' => (UserGoalMilestone::where('user_id', $this->user->id)->where('goal_category', 'like', '%relationship%')->count() > 0) ? true : false,
            'completed_personal_development_goal' => (UserGoalMilestone::where('user_id', $this->user->id)->where('goal_category', 'like', '%personal development%')->count() > 0) ? true : false,
            'all_goals_completed' => (UserGoalMilestone::where('user_id', $this->user->id)->count() > 4) ? true : false,
            'completed_leisure_goal' => (UserGoalMilestone::where('user_id', $this->user->id)->where('goal_category', 'like', '%leisure%')->count() > 0) ? true : false,
            '28_insightful_days' => ($this->user->total_logins >= 28) ? true : false,
            '14_insightful_days' => ($this->user->total_logins >= 14) ? true : false,
            '56_insightful_days' => ($this->user->total_logins >= 56) ? true : false,
            '112_insightful_daya' => ($this->user->total_logins >= 112) ? true : false,
            '40_journals_created' => (ActivityLog::where('user_id', $this->user->id)->where('activity', 'store_journal')->count() >= 40) ? true : false
        ];

        return response([
            'status' => 'success',
            'message' => 'Milestones fetched successfully',
            'data' => $data
        ], 200);
    }
}
