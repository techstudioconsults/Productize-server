<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'user.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'users',
    'middleware' => 'auth:sanctum'
], function () {
    Route::get('/me', [UserController::class, 'show']);

    Route::post('/me', [UserController::class, 'update']);


    Route::post('/change-password', [UserController::class, 'changePassword']);
});
