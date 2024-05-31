<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 22-05-2024
 */

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;


    Route::middleware('auth:sanctum')->group(function () {
        // route to create a new review
        Route::post('/reviews/products/{product}', [ReviewController::class, 'store'])->name('store');
    });


    
     Route::get('/reviews/products/{product}', [ReviewController::class, 'findByProduct'])->name('findByProduct');
     Route::get('/reviews', [ReviewController::class, 'index'])->name('index');