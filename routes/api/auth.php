<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::group([
    'as' => 'auth.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'auth'
], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/redirect', [AuthController::class, 'oAuthRedirect']);
    Route::post('/callback', [AuthController::class, 'oAuthCallback']);

    Route::get('email/verify/{id}', [AuthController::class, 'verify'])->name('verification.verify'); // Make sure to keep this as your route name

    Route::get('email/resend', [AuthController::class, 'resendLink'])->name('verification.resend')->middleware('auth:sanctum');

    Route::get('/test', [AuthController::class, 'test']);
});
