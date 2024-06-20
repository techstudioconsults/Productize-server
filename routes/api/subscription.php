<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::controller(SubscriptionController::class)
    ->prefix('subscriptions')
    ->as('subscription.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:allowed,App\Models\Subscription',
    ])
    ->group(function () {
        Route::get('/', 'index')->middleware('abilities:role:super_admin')->name('index');

        Route::post('/', 'store')->name('store');

        Route::get('/billing', 'billing')->name('billing');

        Route::get('/{subscription}/manage', 'manage')->middleware('can:manage,subscription')->name('manage');

        Route::get('/{subscription}/cancel', 'cancel')->middleware('can:cancel,subscription')->name('cancel');
    });
