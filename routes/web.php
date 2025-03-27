<?php

use App\Mail\AccountCreated;
use App\Models\Funnel;
use App\Notifications\ProductPurchased;
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

    return view('pages.home', compact('environment'));
});

Route::get('/mail', function() {
    $funnel = Funnel::find('9e61941d-e97d-45d7-9df5-317ff6128c4d');
    return (new ProductPurchased($funnel->product, $funnel))->toMail($funnel->product->user);
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
