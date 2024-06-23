<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * @author Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 22-06-2024
 *
 * Repository class generator.
 * Example: php artisan make:repository ExampleRepository --model=Example
 *
 * Model base class `Model` if the model flag is not passed.
 */
class MakeRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name} {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

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
     *
     * @return mixed
     */
    public function handle()
    {
        // Retrieve the name of the repository class
        $name = $this->argument('name');

        // Retrieve the model class
        $model = $this->option('model');

        // Define the path to store the repository
        $path = app_path('Repositories/'.$name.'.php');

        // Check if file already exists
        if ($this->files->exists($path)) {
            $this->error('Repository class already exists!');

            return;
        }

        if (! $model) {
            $this->error('Model for repository not passed. Example usage: php artisan make:repository ExampleRepository --model=ExampleModel');

            return;
        }

        // Create the directory if it doesn't exist
        $this->makeDirectory($path);

        // Create the class file with predefined methods
        $stub = $this->getStub();

        $stub = str_replace('DummyRepository', $name, $stub);
        $stub = str_replace('DummyModel', $model, $stub);

        $this->files->put($path, $stub);

        $this->info('Repository created successfully.');
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

            namespace App\Repositories;

            use App\Models\DummyModel;
            use Illuminate\Database\Eloquent\Builder;
            use Illuminate\Database\Eloquent\Collection;
            use Illuminate\Database\Eloquent\Model;

            class DummyRepository extends Repository
            {
                public function create(array $entity): DummyModel
                {
                    return DummyModel::create($entity);
                }

                public function query(array $filter): Builder
                {
                    return DummyModel::query()->where($filter);
                }

                public function find(?array $filter): ?Collection
                {
                    return DummyModel::where($filter)->get();
                }

                public function findById(string $id): ?DummyModel
                {
                    return DummyModel::find($id);
                }

                public function findOne(array $filter): ?DummyModel
                {
                    return DummyModel::where($filter)->first();
                }

                public function update(Model $entity, array $updates): DummyModel
                {
                    $entity->update($updates);
                    return $entity;
                }
            }
            STUB;
    }

    /**
     * Create the directory for the class if it doesn't exist.
     *
     * @param  string  $path
     * @return void
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true, true);
        }
    }
}
