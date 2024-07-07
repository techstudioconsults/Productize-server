<?php

use App\Http\Controllers\DigitalProductController;
use Illuminate\Support\Facades\Route;

Route::controller(DigitalProductController::class)
    ->prefix('digitalProducts')
    ->as('digitalProduct.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:allowed,App\Models\DigitalProduct',
        'can:premium,App\Models\DigitalProduct',
    ])
    ->group(function () {
        Route::post('/', 'store')->name('store');

        Route::get('/products/{product}', 'product')->name('product')->middleware('can:viewByProduct,product');

        Route::get('{digitalProduct}', 'show')->name('show')->middleware('can:view,digitalProduct');

        Route::get('/categories', 'categories')->withoutMiddleware([
            'auth:sanctum',
            'can:allowed,App\Models\DigitalProduct',
            'can:premium,App\Models\DigitalProduct',
        ])->name('categories');

        Route::put('/{resources}', 'update')->name('update');
    });
