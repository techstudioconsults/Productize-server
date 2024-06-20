<?php

namespace App\Console;

use App\Helpers\Schedules\EndFreeTrial;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(new EndFreeTrial)
            ->hourly();
        // ->environments(['staging', 'production']);
        // ->withoutOverlapping(); // Prevent schduler from overlapping. I.e, if the previous instance is still running, it will wait.
        // ->onSuccess(function () {
        //     // The task succeeded...
        // })
        // ->onFailure(function () {
        //     // The task failed...
        // });
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
