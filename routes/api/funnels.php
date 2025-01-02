<?php

use App\Http\Controllers\FunnelController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'funnels.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'funnels',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('/me', [FunnelController::class, 'user']);
    Route::get('/{funnel}', [FunnelController::class, 'show']);
    Route::get('/{funnel}/restore', [FunnelController::class, 'restore'])->name('restore');
    Route::post('/', [FunnelController::class, 'store']);
    Route::patch('/{funnel}', [FunnelController::class, 'update']);
    Route::delete('/{funnel}', [FunnelController::class, 'delete']);
});
