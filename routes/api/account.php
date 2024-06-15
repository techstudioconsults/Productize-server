<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::controller(AccountController::class)
    ->prefix('accounts')
    ->as('account.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:allowed,App\Models\Account',
        'can:subscribed,App\Models\Account'
    ])
    ->group(function () {
        Route::get('/', 'index')->name('index');

        Route::post('/', 'store')->name('store');

        Route::get('/bank-list', 'bankList')->name('bank-list');

        Route::patch('/{account}', 'update')->middleware('can:update,account')->name('update');
    });
