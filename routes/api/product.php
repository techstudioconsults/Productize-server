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
    Route::post('/', [ProductController::class, 'store'])->name('store');

    Route::get('/', [ProductController::class, 'index'])->withoutMiddleware([
        'auth:sanctum',
        'can:allowed,App\Models\Product',
    ])->name('index');

    Route::get('/users', [ProductController::class, 'user'])->name('user');

    Route::get('/users/top-products', [ProductController::class, 'getUserTopProducts'])->name('user.top-products');

    Route::get('/analytics', [ProductController::class, 'analytics'])->name('analytics');

    Route::get('/revenues', [ProductController::class, 'productsRevenue'])->name('revenue');

    Route::get('/records', [ProductController::class, 'records'])->name('record');

    Route::get('/downloads', [ProductController::class, 'downloads'])->name('download');

    Route::get('/top-products', [ProductController::class, 'topProducts'])->withoutMiddleware([
        'auth:sanctum',

    ])->name('top-products');

    Route::get('/tags', [ProductController::class, 'tags'])->withoutMiddleware([
        'auth:sanctum',
    ])->name('tags');

    Route::get('/{product}', [ProductController::class, 'show'])->name('show')->middleware('can:view,product');

    Route::get('/{product}/restore', [ProductController::class, 'restore'])->middleware('can:restore,product')->name('restore');

    Route::get('/{product:slug}/{slug}', [ProductController::class, 'slug'])
        ->name('slug')
        ->withoutMiddleware([
            'auth:sanctum',
            'can:allowed,App\Models\Product',
            'can:premium,App\Models\Product',
        ]);

    Route::put('/{product}', [ProductController::class, 'update'])->middleware('can:update,product')->name('update');

    Route::patch('/{product}/publish', [ProductController::class, 'togglePublish'])->middleware([
        'can:update,product',
        'can:premium,App\Models\Product',
    ])->name('publish');

    Route::delete('/{product}', [ProductController::class, 'delete'])->middleware('can:delete,product')->name('delete');

    Route::delete('/{product}/force', [ProductController::class, 'forceDelete'])->middleware('can:forceDelete,product')->name('delete.force');
});
