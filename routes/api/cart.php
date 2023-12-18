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
        Route::post('/', 'store');

        Route::get('/', 'show');
    });
