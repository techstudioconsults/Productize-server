<?php

use App\Http\Controllers\SkillSellingController;
use Illuminate\Support\Facades\Route;

Route::controller(SkillSellingController::class)
    ->prefix('skillSellings')
    ->as('skillSelling.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:allowed,App\Models\SkillSelling',
        'can:premium,App\Models\SkillSelling',
    ])
    ->group(function () {
        Route::post('/', 'store')->name('store');

        Route::get("/products/{product}", "show")->name('show');

        Route::get('/categories', 'categories')->withoutMiddleware([
            'auth:sanctum',
            'can:allowed,App\Models\SkillSelling',
            'can:premium,App\Models\SkillSelling',
        ])->name('categories');

        Route::put('/{skillSelling}', 'update')->name('update');
    });
