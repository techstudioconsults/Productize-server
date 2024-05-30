<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::controller(AccountController::class)
    ->prefix('accounts')
    ->as('account.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
    ])
    ->group(function () {
        Route::post('/', 'store')->name('store');

        Route::get('/', 'index')->name('index');
    });
