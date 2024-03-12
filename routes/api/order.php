<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::controller(OrderController::class)
    ->prefix('orders')
    ->as('order.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:allowed,App\Models\Order',
        'can:premium,App\Models\Order',
    ])
    ->group(function () {
        Route::get('/', 'index');

        Route::get('/download', 'downloadList');

        Route::get('/{order}', 'show')->middleware('can:view,order');

        Route::get('/products/{product}', 'showByProduct');

        Route::get('/customers/{customer}', 'showByCustomer');
    });
