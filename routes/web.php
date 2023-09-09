<?php

use App\Mail\WelcomeMail;
use App\Models\User;
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
    return view('emails.welcome-email');
    // return view('welcome');

    // $user = User::find("9a13ba5d-a525-4093-89c7-ed47aea527fb");

// return new App\Mail\WelcomeMail($user);
});
