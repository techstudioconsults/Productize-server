<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::controller(WebhookController::class)
    ->prefix('webhooks')
    ->as('webhook.')
    ->namespace("\App\Http\Controllers")->group(function () {
        Route::post('/paystack', 'paystack')->name('paystack');
    });
