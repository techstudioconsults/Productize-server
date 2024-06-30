<?php

namespace App\Traits;

use Illuminate\Filesystem\Filesystem;

trait HasFileSystem
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $files;

    /**
     * Create the directory for the class if it doesn't exist.
     *
     * @param  string  $path
     * @return void
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true, true);
        }
    }
}
