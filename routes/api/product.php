<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/**
 * @see \App\Policies\ProductPolicy for Authorization middleware
 */
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

    Route::get('/users', [ProductController::class, 'findByUser']);

    Route::get('/analytics', [ProductController::class, 'analytics']);

    Route::get('/download', [ProductController::class, 'downloadList']);

    Route::get('/{product}', [ProductController::class, 'show'])->middleware('can:view,product');

    Route::get('/{product}/restore', [ProductController::class, 'restore'])->middleware('can:restore,product');

    Route::put('/{product}', [ProductController::class, 'update'])->middleware('can:update,product');

    Route::patch('/{product}/status', [ProductController::class, 'updateStatus'])->middleware('can:update,product');

    Route::delete('/{product}', [ProductController::class, 'delete'])->middleware('can:delete,product');

    Route::delete('/{product}/force', [ProductController::class, 'forceDelete'])->middleware('can:forceDelete,product');
});
