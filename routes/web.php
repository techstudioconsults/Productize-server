<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Notifications\WelcomeNotification;
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
    // return view('mail.welcome');
    // return view('welcome');

    $user = User::find("9a188fda-69a2-4af6-ae7a-059d3733bd26");

    return (new WelcomeNotification($user))
                ->toMail($user);

// return new App\Notifications\WelcomeNotification($user)->render();
});
