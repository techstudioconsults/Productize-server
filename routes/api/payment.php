<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'payment.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'payments',
    'middleware' => 'auth:sanctum'
], function () {
    Route::get('/paystack/subscribe', [PaymentController::class, 'createPaystackSubscription']);
    Route::post('/paystack/webhook', [PaymentController::class, 'handlePaystackWebHook']);
});
