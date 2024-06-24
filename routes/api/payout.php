<?php

use App\Http\Controllers\PayoutController;
use Illuminate\Support\Facades\Route;

Route::controller(PayoutController::class)
    ->prefix('payouts')
    ->as('payout.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:allowed,App\Models\Payout',
        'can:subscribed,App\Models\Payout',
    ])
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('abilities:role:super_admin');

        Route::get('/user', 'user')->name('user');

        Route::get('/download', 'download')->name('download');
    });
