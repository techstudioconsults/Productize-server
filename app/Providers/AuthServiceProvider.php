<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Asset;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Policies\AssetPolicy;
use App\Policies\CartPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Product::class => ProductPolicy::class,
        Customer::class => CustomerPolicy::class,
        Order::class => OrderPolicy::class,
        Cart::class => CartPolicy::class,
        Asset::class => AssetPolicy::class,
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
