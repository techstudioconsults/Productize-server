<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'product.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'products',
    'middleware' => [
        'auth:sanctum',
        'can:allowed,App\Models\Product',
        'can:premium,App\Models\Product',
    ]
], function () {
    Route::post('/', [ProductController::class, 'store']);

    Route::get('/', [ProductController::class, 'index']);

    Route::get('/{product}', [ProductController::class, 'show'])->middleware('can:view,product');
});
