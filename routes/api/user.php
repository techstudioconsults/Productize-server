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

    Route::get('/notifications', [UserController::class, 'notifications'])->name('notifications');

    Route::post('/', [UserController::class, 'store'])->middleware('abilities:role:super_admin')->name('store');

    Route::post('/me', [UserController::class, 'update']);

    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::patch('/{user}/revoke-admin-role', [UserController::class, 'revokeAdminRole'])
        ->middleware('abilities:role:super_admin')
        ->name('revoke-admin-role');

    Route::delete('/{user}', [UserController::class, 'deleteAdmin'])
        ->middleware('abilities:role:super_admin')
        ->name('delete-admin');

    Route::put('/update/{user}', [UserController::class, 'updateAdmin'])
        ->middleware('abilities:role:super_admin')
        ->name('updateAdmin');

    Route::post('/kyc', [UserController::class, 'updateKyc'])->name('kyc');

    Route::post('/notifications', [UserController::class, 'readNotifications'])->name('notifications.read');
});
