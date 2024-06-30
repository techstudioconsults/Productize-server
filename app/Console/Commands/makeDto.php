<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * @author Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 30-06-2024
 */
class makeDto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:dto {name} {--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
