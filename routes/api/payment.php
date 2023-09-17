<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'payment.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'payments',
    'middleware' => ['auth:sanctum', 'can:allowed,App\Models\Payment']  // Payment Policy
], function () {
    Route::get('/paystack/subscribe', [PaymentController::class, 'createPaystackSubscription']);

    Route::get('/paystack/subscribe/enable', [PaymentController::class, 'enablePaystackSubscription'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/paystack/subscribe/manage', [PaymentController::class, 'managePaystackSubscription'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::post('/paystack/webhooks', [PaymentController::class, 'handlePaystackWebHook'])
        ->withoutMiddleware(['auth:sanctum', 'can:subscribe,App\Models\Payment']);
});
