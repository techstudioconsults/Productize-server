<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'users.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'users',
    'middleware' => ['auth:sanctum', 'can:verified,App\Models\User'],
], function () {
    Route::get('/', [UserController::class, 'index'])->middleware('abilities:role:super_admin')->name('index');

    Route::get('/stats/admin', [UserController::class, 'stats'])->middleware('abilities:role:super_admin')->name('stats.admin');

    Route::get('/me', [UserController::class, 'show'])->withoutMiddleware('can:verified,App\Models\User');

    Route::get('/download', [UserController::class, 'download'])->middleware('abilities:role:super_admin')->name('download');

    Route::post('/me', [UserController::class, 'update']);

    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::post('/request-help', [UserController::class, 'requestHelp']);
});
