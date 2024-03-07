<?php

namespace App\Helpers\Schedules;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EndFreeTrial
{
    public function __invoke()
    {
        Log::channel('schedule')->info('Ending Free Trials!');

        // Get the current date
        $currentDate = Carbon::now();

        // Calculate the date 30 days ago
        // $thirtyDaysAgo = $currentDate->subDays(30);
        $thirtyDaysAgo = $currentDate->subMinute(30);

        try {
            $count = DB::table('users')->where('account_type', '=', 'free_trial')
                ->where('created_at', '<', $thirtyDaysAgo)
                ->update(['account_type' => 'free']);

            Log::channel('schedule')->info('Total Update Count', ['count' => $count]);
        } catch (\Throwable $th) {
            Log::channel('schedule')->error('Error Ending Free Trials!', ['message' => $th->getMessage()]);
        }
    }
}
