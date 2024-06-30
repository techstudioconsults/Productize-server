<?php

namespace App\Console\Schedules;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 19-02-2024
 *
 * Class EndFreeTrial
 *
 * This class handles the scheduled task of ending free trials for users.
 *
 * @package App\Console\Schedules
 */
class EndFreeTrial
{
    /**
     * Invoke method to execute the scheduled task.
     *
     * Logs the beginning of the process, retrieves users who are on a free trial
     * for more than 30 days, and updates their account type to 'free'.
     *
     * @return void
     */
    public function __invoke()
    {
        Log::channel('schedule')->info('Ending Free Trials!');

        // Get the current date
        $currentDate = Carbon::now();

        // Calculate the date 30 days ago
        $thirtyDaysAgo = $currentDate->subDays(30);

        try {
            // Update users who have been on a free trial for more than 30 days
            $count = DB::table('users')->where('account_type', '=', 'free_trial')
                ->where('created_at', '<', $thirtyDaysAgo)
                ->update(['account_type' => 'free']);

            // Log the count of updated users
            Log::channel('schedule')->info('Total Update Count', ['count' => $count]);
        } catch (\Throwable $th) {
            // Log any errors that occur during the process
            Log::channel('schedule')->error('Error Ending Free Trials!', ['message' => $th->getMessage()]);
        }
    }
}
