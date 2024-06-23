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
        Route::get('/', 'index')->middleware('abilities:role:super_admin')->name('index');

        Route::get('/user', 'user')->name('user');

        Route::get('/download', 'downloadList');

        Route::get('/unseen', 'unseen')->name('unseen');

        Route::get('/stats', 'stats')->middleware('abilities:role:super_admin')->name('stats');

        Route::get('/{order}', 'show')->middleware('can:view,order')->name('show');

        Route::get('/products/{product}', 'showByProduct')->name('show.product');

        Route::get('/customers/{customer}', 'showByCustomer')->name('show.customer');

        Route::patch('/seen', 'markseen')->name('seen.mark');
    });
