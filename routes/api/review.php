<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;


// Route::controller(ReviewController::class)
//     ->prefix('reviews')
//     ->as('reviews.')
//     ->namespace("\App\Http\Controllers")
//     ->group(function () {

//         // Route::get('/', 'index');
//         Route::post('/', 'store');
//         // Route::put('/{faq}', 'update');
//         // Route::delete('/{faq}', 'destroy');
//     });

    Route::middleware('auth:sanctum')->group(function () {
        // route to create a new review
        Route::post('/reviews/{productId}', [ReviewController::class, 'store']);
    });


    
     Route::get('/reviews/{productId}', [ReviewController::class, 'getReviewsByProduct']);
     Route::get('/reviews', [ReviewController::class, 'index']);