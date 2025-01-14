<?php

use App\Http\Controllers\EmailMarketingController;
use Illuminate\Support\Facades\Route;

Route::controller(EmailMarketingController::class)
    ->prefix('emailMarketing')
    ->as('emailMarketing.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'premium'
    ])
    ->group(function () {
        Route::post('/token', 'token')->name('token');
    });
