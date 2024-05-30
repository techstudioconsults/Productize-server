<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::controller(SubscriptionController::class)
    ->prefix('subscriptions')
    ->as('subscription.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
    ])
    ->group(function () {
        Route::post('/', 'store')->name('store');

        Route::get('/', 'billing')->middleware('can:view')->name('billing');

        Route::get('/enable/{subscription}', 'enable')->middleware('can:enable,subscription')->name('enable');

        Route::get('/manage/{subscription}', 'manage')->middleware('can:manage,subscription')->name('manage');

        Route::get('/cancel/{subscription}', 'cancel')->middleware('can:cancel,subscription')->name('cancel');
    });
