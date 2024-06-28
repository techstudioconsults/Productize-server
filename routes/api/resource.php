<?php

use App\Http\Controllers\ProductResourceController;
use Illuminate\Support\Facades\Route;

Route::controller(ProductResourceController::class)
    ->prefix('resources')
    ->as('resources.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
    ])
    ->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('/products/{product}', 'product')->name('product');
        Route::delete('/', 'delete')->name('delete');
    });
