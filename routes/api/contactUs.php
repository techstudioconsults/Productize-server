<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 13-07-2024
 */

use App\Http\Controllers\ContactUsController;
use Illuminate\Support\Facades\Route;

Route::controller(ContactUsController::class)
     ->prefix('contactUs')
     ->as('contactus.')
     ->namespace("\App\Http\Controllers")
     ->group(function() {
         
        Route::post('/', 'store')->name('store');
     });
