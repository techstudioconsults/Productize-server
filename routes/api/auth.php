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
    // Route::post('/test', function(Request $request) {
    //     $originalName = $request->photo->getClientOriginalName();
    //     $path  = Storage::putFileAs('images', $request->file('photo'), $originalName);
    //     $url = env('DO_CDN_SPACE_ENDPOINT').'/'.$path;

    //     return new JsonResponse([
    //         'data' => $url
    //     ]);
    // });

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
