<?php

namespace App\Providers;

use App\Helpers\Services\FileGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the FileGenerator class in the service container so that it can be injected into the app controllers
        $this->app->singleton(FileGenerator::class, function ($app) {
            return new FileGenerator();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Set client url dynamically based on the request origin.
         */
        $client_url = request()->header('origin') ?? 'https://tsa-productize.vercel.app';
        config(['app.client_url' => $client_url]);

        config(['services.google.redirect' => $client_url . '/auth/fetching-data/google']);
    }
}
