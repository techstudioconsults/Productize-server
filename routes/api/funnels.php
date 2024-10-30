<?php

use App\Http\Controllers\FunnelController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'funnels.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'funnels',
], function () {
    Route::post('/', [FunnelController::class, 'store']);
});
