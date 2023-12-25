<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'payment.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'payments',
    'middleware' => ['auth:sanctum', 'can:allowed,App\Models\Payment']  // Payment Policy
], function () {
    Route::post('/subscription', [PaymentController::class, 'createSubscription']);

    Route::post('/accounts', [PaymentController::class, 'createSubAccount'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/accounts', [PaymentController::class, 'getAllSubAccounts'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::patch('/accounts/{account}', [PaymentController::class, 'updateSubaccount'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/bank-list', [PaymentController::class, 'getBankList'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::post('/purchase', [PaymentController::class, 'purchase']);

    Route::get('{payment}/subscription/enable', [PaymentController::class, 'enablePaystackSubscription'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('{payment}/subscription/manage', [PaymentController::class, 'managePaystackSubscription'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::post('/paystack/webhooks', [PaymentController::class, 'handlePaystackWebHook'])
        ->withoutMiddleware(['auth:sanctum', 'can:allowed,App\Models\Payment', 'csrf']);
});
