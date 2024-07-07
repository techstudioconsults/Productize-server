<?php

use App\Http\Controllers\AssetController;
use Illuminate\Support\Facades\Route;

Route::controller(AssetController::class)
    ->prefix('assets')
    ->as('assets.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
    ])
    ->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('/products/{product}', 'product')->name('product');
        Route::delete('/', 'delete')->name('delete');
    });
