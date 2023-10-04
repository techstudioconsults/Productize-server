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
 * php artisan make:controller PaymentController --api --model=Payments -r -R
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

// '9', '834f2dea-c8b2-402a-87c2-cc47a7120bbb', 'database', 'default', '{\"uuid\":\"834f2dea-c8b2-402a-87c2-cc47a7120bbb\",\"displayName\":\"App\\\\Notifications\\\\WelcomeNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":5,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"3\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":5:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;s:36:\\\"9a4970c9-edc9-4d90-9ff1-f2f4488c2e3b\\\";}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:37:\\\"App\\\\Notifications\\\\WelcomeNotification\\\":4:{s:49:\\\"\\u0000App\\\\Notifications\\\\WelcomeNotification\\u0000client_url\\\";s:37:\\\"https:\\/\\/productize.techstudio.academy\\\";s:7:\\\"\\u0000*\\u0000user\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";s:36:\\\"9a4970c9-edc9-4d90-9ff1-f2f4488c2e3b\\\";s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"id\\\";s:36:\\\"c54ddf13-3f51-4533-b104-a12a52682206\\\";s:11:\\\"afterCommit\\\";b:1;}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}s:5:\\\"tries\\\";i:5;s:11:\\\"afterCommit\\\";b:1;}\"}}', 'ErrorException: file_put_contents(/var/www/productize/_work/Productize-server/Productize-server/storage/framework/views/5a2e9414bd590ab4f2f4512cc0402a08.php): Failed to open stream: Permission denied in /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php:205\nStack trace:\n#0 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/HandleExceptions.php(254): Illuminate\\Foundation\\Bootstrap\\HandleExceptions->handleError()\n#1 [internal function]: Illuminate\\Foundation\\Bootstrap\\HandleExceptions->Illuminate\\Foundation\\Bootstrap\\{closure}()\n#2 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(205): file_put_contents()\n#3 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/View/Compilers/BladeCompiler.php(192): Illuminate\\Filesystem\\Filesystem->put()\n#4 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(64): Illuminate\\View\\Compilers\\BladeCompiler->compile()\n#5 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/View/View.php(195): Illuminate\\View\\Engines\\CompilerEngine->get()\n#6 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/View/View.php(178): Illuminate\\View\\View->getContents()\n#7 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/View/View.php(147): Illuminate\\View\\View->renderContents()\n#8 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(65): Illuminate\\View\\View->render()\n#9 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Notifications/Channels/MailChannel.php(115): Illuminate\\Mail\\Markdown->render()\n#10 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Collections/helpers.php(224): Illuminate\\Notifications\\Channels\\MailChannel->Illuminate\\Notifications\\Channels\\{closure}()\n#11 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(398): value()\n#12 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(377): Illuminate\\Mail\\Mailer->renderView()\n#13 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(289): Illuminate\\Mail\\Mailer->addContent()\n#14 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Notifications/Channels/MailChannel.php(69): Illuminate\\Mail\\Mailer->send()\n#15 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(148): Illuminate\\Notifications\\Channels\\MailChannel->send()\n#16 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(106): Illuminate\\Notifications\\NotificationSender->sendToNotifiable()\n#17 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\\Notifications\\NotificationSender->Illuminate\\Notifications\\{closure}()\n#18 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php(109): Illuminate\\Notifications\\NotificationSender->withLocale()\n#19 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Notifications/ChannelManager.php(54): Illuminate\\Notifications\\NotificationSender->sendNow()\n#20 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Notifications/SendQueuedNotifications.php(112): Illuminate\\Notifications\\ChannelManager->sendNow()\n#21 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Notifications\\SendQueuedNotifications->handle()\n#22 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/Util.php(41): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#23 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(93): Illuminate\\Container\\Util::unwrapIfClosure()\n#24 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(37): Illuminate\\Container\\BoundMethod::callBoundMethod()\n#25 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/Container.php(662): Illuminate\\Container\\BoundMethod::call()\n#26 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(128): Illuminate\\Container\\Container->call()\n#27 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(141): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}()\n#28 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(116): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#29 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(132): Illuminate\\Pipeline\\Pipeline->then()\n#30 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(124): Illuminate\\Bus\\Dispatcher->dispatchNow()\n#31 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(141): Illuminate\\Queue\\CallQueuedHandler->Illuminate\\Queue\\{closure}()\n#32 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(116): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#33 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(126): Illuminate\\Pipeline\\Pipeline->then()\n#34 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(70): Illuminate\\Queue\\CallQueuedHandler->dispatchThroughMiddleware()\n#35 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(98): Illuminate\\Queue\\CallQueuedHandler->call()\n#36 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(439): Illuminate\\Queue\\Jobs\\Job->fire()\n#37 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(389): Illuminate\\Queue\\Worker->process()\n#38 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(176): Illuminate\\Queue\\Worker->runJob()\n#39 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(138): Illuminate\\Queue\\Worker->daemon()\n#40 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(121): Illuminate\\Queue\\Console\\WorkCommand->runWorker()\n#41 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#42 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/Util.php(41): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#43 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(93): Illuminate\\Container\\Util::unwrapIfClosure()\n#44 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(37): Illuminate\\Container\\BoundMethod::callBoundMethod()\n#45 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Container/Container.php(662): Illuminate\\Container\\BoundMethod::call()\n#46 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\\Container\\Container->call()\n#47 /var/www/productize/_work/Productize-server/Productize-server/vendor/symfony/console/Command/Command.php(326): Illuminate\\Console\\Command->execute()\n#48 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Console/Command.php(181): Symfony\\Component\\Console\\Command\\Command->run()\n#49 /var/www/productize/_work/Productize-server/Productize-server/vendor/symfony/console/Application.php(1081): Illuminate\\Console\\Command->run()\n#50 /var/www/productize/_work/Productize-server/Productize-server/vendor/symfony/console/Application.php(320): Symfony\\Component\\Console\\Application->doRunCommand()\n#51 /var/www/productize/_work/Productize-server/Productize-server/vendor/symfony/console/Application.php(174): Symfony\\Component\\Console\\Application->doRun()\n#52 /var/www/productize/_work/Productize-server/Productize-server/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(201): Symfony\\Component\\Console\\Application->run()\n#53 /var/www/productize/_work/Productize-server/Productize-server/artisan(37): Illuminate\\Foundation\\Console\\Kernel->handle()\n#54 {main}', '2023-10-04 06:49:31'
