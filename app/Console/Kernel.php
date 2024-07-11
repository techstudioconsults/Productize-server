<?php

namespace App\Console;

use App\Console\Schedules\EndFreeTrial;
use App\Console\Schedules\FreeTrialReminder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(new EndFreeTrial)->twiceDaily();
        $schedule->call(new FreeTrialReminder)->daily();

        // Prune Models
        $schedule->command('model:prune')->daily();
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
