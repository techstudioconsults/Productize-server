<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'auth.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'auth'
], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/oauth/redirect', [AuthController::class, 'oAuthRedirect']);
    Route::post('/oauth/callback', [AuthController::class, 'oAuthCallback']);

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('/reset-password', [AuthController::class, 'ResetPassword'])->name('password.update');

    Route::get('email/verify/{id}', [AuthController::class, 'verify'])->name('verification.verify'); // Make sure to keep this as your route name
    Route::get('email/resend', [AuthController::class, 'resendLink'])->name('verification.resend')->middleware('auth:sanctum');

    Route::get('/test', [AuthController::class, 'test']);
});
