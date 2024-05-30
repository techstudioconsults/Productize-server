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

        Route::get('/', 'index')->name('index');

        Route::get('/{subscription}', 'show')->middleware('can:view,subscription')->name('show');

        Route::patch('/{subscription}', 'update')->middleware('can:update,subscription')->name('update');

        Route::delete('/{subscription}', 'delete')->middleware('can:delete,subscription')->name('delete');
    });
