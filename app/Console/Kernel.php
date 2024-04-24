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
            $notification = new NotificationController();
            $notification->to_send();
        })->everySixHours();

        $schedule->call(function(){
            NotificationController::check_subscriptions();
        })->everySixHours();
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
