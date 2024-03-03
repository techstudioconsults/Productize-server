<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::controller(OrderController::class)
    ->prefix('orders')
    ->as('order.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:premium,App\Models\Sale',
    ])
    ->group(function () {
        Route::get('/', 'index');

        Route::get('/{order}', 'show')->middleware('can:view,order');

        Route::get('/products/{product}', 'showByProductId');
    });
