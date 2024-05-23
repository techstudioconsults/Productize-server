<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */

use App\Http\Controllers\CommunityController;
use Illuminate\Support\Facades\Route;


Route::controller(CommunityController::class)
    ->prefix('community')
    ->as('community.')
    ->namespace("\App\Http\Controllers")
    ->group(function () {

        Route::get('/', 'index');
        Route::post('/create', 'store');
    });
