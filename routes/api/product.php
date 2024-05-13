<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/**
 * @see \App\Policies\ProductPolicy for Authorization middleware
 * @see https://laravel.com/docs/10.x/routing#route-group-controllers
 */
Route::group([
    'as' => 'product.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'products',
    'middleware' => [
        'auth:sanctum',
    ]
], function () {
    Route::post('/', [ProductController::class, 'store']);

    Route::get('/', [ProductController::class, 'index'])->withoutMiddleware([
        'auth:sanctum',
        'can:allowed,App\Models\Product',
    ])->name('index');

    Route::get('/users', [ProductController::class, 'findByUser']);

    Route::get('/analytics', [ProductController::class, 'analytics']);

    Route::get('/revenues', [ProductController::class, 'productsRevenue']);

    Route::get('/download', [ProductController::class, 'downloadList']);

    Route::get('/downloads', [ProductController::class, 'downloads']);

    Route::get('/top-products', [ProductController::class, 'getUserTopProducts'])->name('user-top-products');

    Route::get('/top-products/all', [ProductController::class, 'topProducts'])->withoutMiddleware([
        'auth:sanctum',

    ])->name('top-products');

    Route::get('/tags', [ProductController::class, 'tags'])->withoutMiddleware([
        'auth:sanctum',
    ]);

    Route::get('/{product}', [ProductController::class, 'show'])->middleware('can:view,product');

    Route::get('/{product}/restore', [ProductController::class, 'restore'])->middleware('can:restore,product');

    Route::get('/{product:slug}/{slug}', [ProductController::class, 'findBySlug'])
        ->withoutMiddleware([
            'auth:sanctum',
            'can:allowed,App\Models\Product',
            'can:premium,App\Models\Product',
        ]);

    Route::put('/{product}', [ProductController::class, 'update'])->middleware('can:update,product');

    Route::patch('/{product}/publish', [ProductController::class, 'togglePublish'])->middleware([
        'can:update,product',
        'can:premium,App\Models\Product',
    ]);

    Route::delete('/{product}', [ProductController::class, 'delete'])->middleware('can:delete,product');

    Route::delete('/{product}/force', [ProductController::class, 'forceDelete'])->middleware('can:forceDelete,product');
});
