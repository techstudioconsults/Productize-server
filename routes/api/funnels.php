<?php

use App\Http\Controllers\FunnelController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'funnels.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'funnels',
    'middleware' => ['auth:sanctum']
], function () {
    Route::post('/', [FunnelController::class, 'store']);
});
