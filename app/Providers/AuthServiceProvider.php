<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
// use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Payment::class => PaymentPolicy::class,
        User::class => UserPolicy::class,
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        Customer::class => CustomerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate::define('email-verified', function (User $user) {

        //     if ($user->hasVerifiedEmail()) {
        //         return true;
        //     } else {
        //         return false;
        //     }
        // });
    }
}
