<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'product.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'products',
    'middleware' => ['auth:sanctum', 'can:allowed,App\Models\Product']
], function () {
    Route::post('/', [UserController::class, 'store']);
});
