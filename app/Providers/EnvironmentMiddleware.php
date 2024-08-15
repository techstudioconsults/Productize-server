<?php

namespace App\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class EnvironmentMiddleware extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $kernel = $this->app->make(Kernel::class);

        if (app()->environment(['production', 'staging'])) {
            $kernel->pushMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        }
    }
}
