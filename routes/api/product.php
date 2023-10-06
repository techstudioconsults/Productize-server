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

    Route::get('/analytics', [ProductController::class, 'analytics']);

    Route::get('/download', [ProductController::class, 'downloadList']);

    Route::get('/{product}', [ProductController::class, 'show'])->middleware('can:view,product');

    Route::patch('/{product}/status', [ProductController::class, 'updateStatus'])->middleware('can:update,product');
});
