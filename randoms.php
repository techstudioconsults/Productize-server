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

/**
 * use sanctum ability middleware on routes.
 * Ensure to have added middlware alias in app/kernel
 * https://laravel.com/docs/10.x/sanctum#token-ability-middleware
  Route::get('/orders', function () {
    // Token has the "check-status" or "place-orders" ability...
})->middleware(['auth:sanctum', 'ability:check-status,place-orders']);
 */


/**
 * Add to ci/cd to clean up expired tokens
 * php artisan sanctum:prune-expired
 * sudo chmod -R 777 storage
 */


/**
 * CICD
 * php artisan queue:restart
 * Install the server https://laravel.com/docs/10.x/queues#supervisor-configuration

 [program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/app.com/artisan queue:work sqs --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=forge
numprocs=8
redirect_stderr=true
stdout_logfile=/home/forge/app.com/worker.log
stopwaitsecs=3600
 */

// php artisan queue:work

/**
 * Rendering mailables in browser
 * $user = User::find("9a188fda-69a2-4af6-ae7a-059d3733bd26");
 * return (new WelcomeNotification($user))->toMail($user); // Notification
 * return new EmailVerification($user); // Mails
 */

/**
 * Give write access to directory on server
 *sudo chown -R www-data:www-data /var/www/productize/_work/Productize-server/Productize-server/storage/framework/views
 */

/**
 * php artisan make:model Flight -mfs
 * php artisan make:controller PhotoController --model=Photo --resource --requests
 *
 * php artisan migrate:refresh --step=5
 */

// $flight = Flight::findOr(1, function () {
//     // ...
// });

// $flight = Flight::where('legs', '>', 3)->firstOr(function () {
//     // ...
// });

// $comment = Post::find(1)->comments()
//     ->where('title', 'foo')
//     ->first();

// $posts = Post::whereBelongsTo($user)->get(); //

/**
 * Request helpers
 * request()->getSchemeAndHttpHost();
 * request()->getHost();
 * request()->getHttpHost();
 */

// $response->assertJsonFragment(['name' => 'Taylor Otwell']);
