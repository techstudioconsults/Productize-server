<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'user.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'users',
    'middleware' => ['auth:sanctum', 'can:verified,App\Models\User'],
], function () {
    Route::get('/stats/admin', [UserController::class, 'stats'])->middleware('abilities:role:super_admin')->name('stats.admin');

    Route::get('/me', [UserController::class, 'show'])->withoutMiddleware('can:verified,App\Models\User');

    Route::post('/me', [UserController::class, 'update']);

    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::post('/request-help', [UserController::class, 'requestHelp']);
});
