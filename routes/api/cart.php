<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::controller(CartController::class)
    ->prefix('carts')
    ->as('cart.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
    ])
    ->group(function () {
        Route::post('/', 'store')->name('store');

        Route::get('/', 'index')->name('index');

        Route::post('/clear', 'clear')->name('clear');

        Route::post('/funnel', 'funnel')->name('funnel')->withoutMiddleware('auth:sanctum');

        Route::get('/{cart}', 'show')->middleware('can:view,cart')->name('show');

        Route::patch('/{cart}', 'update')->middleware('can:update,cart')->name('update');

        Route::delete('/{cart}', 'delete')->middleware('can:delete,cart')->name('delete');
    });
