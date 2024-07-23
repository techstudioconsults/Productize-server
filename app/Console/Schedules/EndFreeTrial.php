<?php

namespace App\Console\Schedules;

use App\Models\User;
use App\Notifications\FreeTrialEnded;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Notification;

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

        // Calculate the date 30 days ago without modifying the original date object
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        try {
            // Retrieve users who have been on a free trial for more than 30 days
            $users = User::where('account_type', '=', 'free_trial')
                ->where('created_at', '<', $thirtyDaysAgo)
                ->get();

            // Update the account type of the retrieved users
            $count = $users->count();

            if ($count > 0) {
                User::whereIn('id', $users->pluck('id'))
                    ->update(['account_type' => 'free']);

                // Send notifications to users whose account type was updated
                Notification::send($users, new FreeTrialEnded);

                // Log the count of updated users
                Log::channel('schedule')->info('Total Update Count', ['count' => $count]);
            } else {
                Log::channel('schedule')->info('No users found with expiring free trials.');
            }
        } catch (\Throwable $th) {
            // Log any errors that occur during the process
            Log::channel('schedule')->error('Error Ending Free Trials!', ['message' => $th->getMessage()]);
        }
    }
}
