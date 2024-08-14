<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    $environment = env('APP_ENV', 'production');

    return view('welcome', compact('environment'));
});

/**
 * @Intuneteq
 *
 * This view is provided by laravel
 *
 * @see https://laravel.com/docs/10.x/passwords
 */
Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');
