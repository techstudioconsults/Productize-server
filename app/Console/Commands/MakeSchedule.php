<?php

namespace App\Console\Commands;

use App\Traits\HasFileSystem;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * @author Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 30-06-2024
 *
 * Create Schedule classes.
 *
 * Example: php artisan make:schedule ScheduleClass
 */
class MakeSchedule extends Command
{
    use HasFileSystem;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:schedule {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new schedule class';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Retrieve the name of the Schedule class
        $name = $this->argument('name');

        // Define the path to store the repository
        $path = app_path('Console/Schedules/' . $name . '.php');

        // Check if file already exists
        if ($this->files->exists($path)) {
            $this->error('Schedule class already exists!');

            return;
        }

         // Create the directory if it doesn't exist
         $this->makeDirectory($path);

         // Create the class file with predefined methods
         $stub = $this->getStub();

         $stub = str_replace('DummyClass', $name, $stub);

         $this->files->put($path, $stub);

         $this->info('Schedule created successfully.');
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return <<<'STUB'
            <?php

            namespace App\Console\Schedules;

            use Illuminate\Support\Facades\Log;

            /**
             * @author
             *
             * @version
             *
             * @since
             *
             * Class DummyClass
             *
             * @package App\Console\Schedules
             */
            class DummyClass
            {
                /**
                 * Invoke method to execute the scheduled task.
                 *
                 * @return void
                 */
                public function __invoke()
                {
                }
            }
            STUB;
    }
}
