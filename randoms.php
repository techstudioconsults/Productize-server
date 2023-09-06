<?

/**
 * Random code snippets are pasted here
 */

/**
 * Generate url to publicly accessible files in /storage/app/public
 * Ensure to run this first: php artisan storage:link
 * check links array in config/filesystems.php for more info.
 */
echo asset('storage/file.txt');
echo asset('images/img.png');
echo asset('videos/video.mp3');


/**
 * Uploaded file class methods
 * https://github.com/symfony/symfony/blob/6.0/src/Symfony/Component/HttpFoundation/File/UploadedFile.php
 */

 /**
  * uploading to digital ocean spaces
 $originalName = $request->photo->getClientOriginalName();
 $path  = Storage::putFileAs('images', $request->file('photo'), $originalName);
 $url = env('DO_CDN_SPACE_ENDPOINT').'/'.$path;
 */

/**
 * Php ide helper for vs code
 * composer require --dev barryvdh/laravel-ide-helper
 *
 * Add this to the providers array in config/app.php
 * Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class
 *
 * Usage
 * php artisan ide-helper:generate
 */
