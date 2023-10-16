<?php

use App\Models\Product;
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
    $products = Product::where('user_id', '9a27144d-7a1e-459b-87d4-797cf78d9c0b')->get();

    $columns = array('Title', 'Price', 'Sales', 'Type', 'Status');

    

    return view('pdf.products-list', ['products' => $products, 'columns' => $columns]);
    // return view('welcome');
});

/**
 * @Intuneteq
 *
 * This view is provided by laravel
 * @see https://laravel.com/docs/10.x/passwords
 */
Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');
