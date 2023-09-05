<?php

namespace App\Helpers\Routes;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RouteHelpers
{
    public static function includeRouteFiles($folder)
    {
        //Iterate through the v1 folder recursively
        $dirIterator = new RecursiveDirectoryIterator($folder);

        /** @var RecursiveDirectoryIterator | RecursiveDirectoryIterator $it */
        $it = new RecursiveIteratorIterator($dirIterator);

        //require the file in the iterator
        while ($it->valid()) {
            //check that iterator is pointing at a file
            if (!$it->isDot() && $it->isFile() && $it->isReadable() && $it->current()->getExtension() === 'php') {
                require $it->key();
            }
            $it->next();
        }
    }
}
