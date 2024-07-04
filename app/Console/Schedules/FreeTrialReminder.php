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

        // Calculate the date 27 days ago without modifying the original date object
        $twentySevenDaysAgo = Carbon::now()->subDays(27);

        try {
            // Retrieve users on the 27th day of their free trial
            $users = DB::table('users')
                ->where('account_type', '=', 'free_trial')
                ->whereBetween('created_at', [$twentySevenDaysAgo->startOfDay(), $twentySevenDaysAgo->endOfDay()])
                ->get();

            // End operation when no user is found
            if ($users->isEmpty()) {
                Log::channel('schedule')->info('No users found with expiring free trials.');

                return;
            }

            // Collect user emails
            $emails = $users->pluck('email');

            // Send reminder emails to the collected emails
            Mail::to($emails->toArray())->send(new FreeTrialEndingReminder());

            // Log the count of users who received the email
            Log::channel('schedule')->info('Free Trial Emails Sent', ['count' => $emails->count()]);
        } catch (\Throwable $th) {
            // Log any errors that occur during the process
            Log::channel('schedule')->error('Error Sending Free Trial Mail', ['message' => $th->getMessage()]);
        }
    }
}
