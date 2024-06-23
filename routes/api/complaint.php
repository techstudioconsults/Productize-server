<?php

/**
 *@author @Intuneteq Tobi Olanitori
 *
 *  @version 1.0
 *
 *  @since 23-06-2024
 */

use App\Http\Controllers\ComplaintController;
use Illuminate\Support\Facades\Route;

Route::controller(ComplaintController::class)
    ->prefix('complaints')
    ->as('complaints.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'abilities:role:super_admin',
    ])
    ->group(function () {
        Route::get('/', 'index')->name('index');

        Route::get('/{complaint}', 'show')->name('show');

        Route::post('/', 'store')->name('store')->withoutMiddleware('abilities:role:super_admin');
    });
