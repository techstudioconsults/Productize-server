<?php

use App\Http\Controllers\EarningController;
use Illuminate\Support\Facades\Route;

Route::controller(EarningController::class)
    ->prefix('earnings')
    ->as('earning.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:allowed,App\Models\Earning',
        'can:subscribed,App\Models\Earning'
    ])
    ->group(function () {
        Route::get('/', 'index')->name('index');
        
        Route::post('/withdraw', 'withdraw')->name('withdraw');
    });
