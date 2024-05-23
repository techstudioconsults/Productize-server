<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */

use App\Http\Controllers\FaqController;
use Illuminate\Support\Facades\Route;


Route::controller(FaqController::class)
    ->prefix('faqs')
    ->as('faq.')
    ->namespace("\App\Http\Controllers")
    ->group(function () {

        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::put('/{faq}', 'update');
        Route::delete('/{faq}', 'destroy');
    });
