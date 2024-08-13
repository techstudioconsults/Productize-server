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
    ],
], function () {
    Route::post('/', [ProductController::class, 'store'])->name('store');

    Route::get('/', [ProductController::class, 'index'])->middleware('abilities:role:super_admin,role:admin')->name('index');

    Route::get('/external', [ProductController::class, 'external'])->withoutMiddleware([
        'auth:sanctum',
        'can:allowed,App\Models\Product',
    ])->name('external');

    Route::get('/users', [ProductController::class, 'user'])->name('user');

    Route::get('/users/top-products', [ProductController::class, 'getUserTopProducts'])->name('user.top-products');

    Route::get('/analytics', [ProductController::class, 'analytics'])->name('analytics');

    Route::get('/revenues', [ProductController::class, 'productsRevenue'])->name('revenue');

    Route::get('/records', [ProductController::class, 'records'])->name('records');

    Route::get('/records/admin', [ProductController::class, 'adminRecords'])
        ->middleware('abilities:role:super_admin,role:admin')
        ->name('records.admin');

    Route::get('/purchased', [ProductController::class, 'purchased'])->name('purchased');

    Route::get('/top-products', [ProductController::class, 'topProducts'])->withoutMiddleware([
        'auth:sanctum',
    ])->name('top-products');

    Route::get('/top-products/admin', [ProductController::class, 'bestSelling'])
        ->middleware('abilities:role:super_admin,role:admin')
        ->name('top-product.admin');

    Route::get('/tags', [ProductController::class, 'tags'])->withoutMiddleware([
        'auth:sanctum',
    ])->name('tags');

    Route::post('/search', [ProductController::class, 'search'])->withoutMiddleware([
        'auth:sanctum',
    ])->name('search');

    Route::post('/{product}/send-congratulations-mail', [ProductController::class, 'sendCongratulations'])->middleware('abilities:role:super_admin')->name('congratulations');

    Route::get('/search', [ProductController::class, 'basedOnSearch'])->withoutMiddleware('auth:sanctum')->name('search.get');

    Route::get('/stats/admin', [ProductController::class, 'stats'])->middleware('abilities:role:super_admin,role:admin')->name('stats');

    Route::get('/types', [ProductController::class, 'types'])->withoutMiddleware('auth:sanctum')->name('types');

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
