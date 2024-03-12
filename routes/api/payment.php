<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'payment.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'payments',
    'middleware' => ['auth:sanctum', 'can:allowed,App\Models\Payment']  // Payment Policy
], function () {
    Route::get('/', [PaymentController::class, 'show']);

    Route::post('/subscription', [PaymentController::class, 'createPaystackSubscription']);

    Route::post('/accounts', [PaymentController::class, 'createPayOutAccount'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/accounts', [PaymentController::class, 'getAllPayOutAccounts'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::post('/payouts', [PaymentController::class, 'initiateWithdrawal'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/payouts', [PaymentController::class, 'getPayouts'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/payouts/download', [PaymentController::class, 'downloadPayoutList'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::patch('/accounts/{account}', [PaymentController::class, 'updatePayOutAccount'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/bank-list', [PaymentController::class, 'getBankList'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::post('/purchase', [PaymentController::class, 'purchase']);

    Route::get('/subscription/enable', [PaymentController::class, 'enablePaystackSubscription'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/subscription/manage', [PaymentController::class, 'managePaystackSubscription'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::post('/subscription/cancel', [PaymentController::class, 'cancelSubscription'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::get('/subscription/billing', [PaymentController::class, 'billing'])
        ->middleware('can:subscribed,App\Models\Payment');

    Route::post('/paystack/webhooks', [PaymentController::class, 'handlePaystackWebHook'])
        ->withoutMiddleware(['auth:sanctum', 'can:allowed,App\Models\Payment', 'csrf']);
});
