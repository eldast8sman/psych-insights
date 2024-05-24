<?php

namespace App\Console;

use App\Http\Controllers\NotificationController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function(){
            NotificationController::check_subscriptions();
            NotificationController::assessment_reminder();
            NotificationController::check_inactivity();
            NotificationController::daily_reminder();
        })->twiceDailyAt(8, 19);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
