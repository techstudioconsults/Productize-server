<?php

namespace App\Helpers\Schedules;

use Illuminate\Support\Facades\Log;

class EndFreeTrial
{
    public function __invoke()
    {
        Log::channel('schedule')->info('Ending Free Trials!');
        // DB::table('myTable')->where('x', '>', 0)->update(['x' => 0]);
    }
}
