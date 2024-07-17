<?php

use App\Http\Controllers\RevenueController;
use Illuminate\Support\Facades\Route;

Route::controller(RevenueController::class)
    ->prefix('revenues')
    ->as('revenue.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'abilities:role:super_admin',
    ])
    ->group(function () {
        Route::get('/', 'index')->name('index');

        Route::get('/daily', 'daily')->name('daily')->withoutMiddleware('abilities:role:super_admin');

        Route::get('/stats', 'stats')->name('stats');

        Route::get('/download', 'download')->name('download');
    });
