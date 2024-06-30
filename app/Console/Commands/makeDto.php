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
 * Create DTO classes.
 *
 * Example: php artisan make:dto ExampleDto
 */
class MakeDto extends Command
{
    use HasFileSystem;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:dto {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Data Transfer Object class';

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
        // Retrieve the name of the DTO class
        $name = $this->argument('name');

        // Define the path to store the repository
        $path = app_path('Dtos/' . $name . '.php');

        // Check if file already exists
        if ($this->files->exists($path)) {
            $this->error('DTO class already exists!');

            return;
        }

        // Create the directory if it doesn't exist
        $this->makeDirectory($path);

        // Create the class file with predefined methods
        $stub = $this->getStub();

        $stub = str_replace('DummyDto', $name, $stub);

        $this->files->put($path, $stub);

        $this->info('DTO created successfully.');
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

            namespace App\Dtos;

            use App\Exceptions\ServerErrorException;

            /**
             * @author
             *
             * @version
             *
             * @since
             *
             * Data Transfer Object
             */
            class DummyDto implements IDtoFactory
            {
                /**
                 * DummyDto constructor.
                 *
                 */
                public function __construct()
                {
                }

                /**
                 * Convert the DTO to an array.
                 *
                 * @return array
                 */
                public function toArray(): array
                {
                    return [];
                }

                /**
                 * Create an instance of DummyDto from an array of data.
                 *
                 * @param array $data
                 * @return self
                 * @throws ServerErrorException
                 */
                public static function create(array $data): self
                {
                    return new DummyDto();
                }
            }
            STUB;
    }
}
