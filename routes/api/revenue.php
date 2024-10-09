<?php

use App\Http\Controllers\RevenueController;
use Illuminate\Support\Facades\Route;

Route::controller(RevenueController::class)
    ->prefix('revenues')
    ->as('revenue.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'abilities:role:super_admin,role:admin',
    ])
    ->group(function () {
        Route::get('/', 'index')->name('index');

        Route::get('/daily', 'daily')->name('daily')->withoutMiddleware('abilities:role:super_admin,role:admin');

        Route::get('/stats', 'stats')->name('stats')->withoutMiddleware('abilities:role:admin');

        Route::get('/download', 'download')->name('download');

        Route::get('/allstats', 'allStats')->name('allstats');
    });
