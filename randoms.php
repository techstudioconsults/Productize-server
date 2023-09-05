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
