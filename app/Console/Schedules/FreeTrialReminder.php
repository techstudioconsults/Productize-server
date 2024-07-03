<?php

namespace App\Console\Schedules;

use App\Mail\FreeTrialEndingReminder;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use Mail;

/**
 * @author @Intuneteq
 *
 * @version 1.0
 *
 * @since 03-07-2024
 *
 * Class FreeTrialReminder
 *
 * @package App\Console\Schedules
 */
class FreeTrialReminder
{
    /**
     * Invoke method to execute the scheduled task.
     *
     * @return void
     */
    public function __invoke()
    {
        Log::channel('schedule')->info('Scanning For Expiring Free Trials!');

        // Get the current date
        $currentDate = Carbon::now();

        // Calculate the date 30 days ago
        $twentySevenDaysAgo = $currentDate->subDays(27);

        try {
            // Retrieve users on the 27th day of their free trial
            $users = DB::table('users')->where('account_type', '=', 'free_trial')
                ->whereBetween('created_at', [$twentySevenDaysAgo->startOfDay(), $twentySevenDaysAgo->endOfDay()])
                ->get();

            // End operation when no user is found
            if ($users->isEmpty()) return;

            $emails = $users->map(function (User $user) {
                return $user->email;
            });

            Mail::to($emails->toArray())->send(new FreeTrialEndingReminder());

            // Log the count of updated users
            Log::channel('schedule')->info('Free Trial Emails Sent');
        } catch (\Throwable $th) {
            // Log any errors that occur during the process
            Log::channel('schedule')->error('Error Sending Free Trial Mail', ['message' => $th->getMessage()]);
        }
    }
}
