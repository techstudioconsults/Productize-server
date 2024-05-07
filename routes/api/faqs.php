<?php

use App\Http\Controllers\FaqsController;
use Illuminate\Support\Facades\Route;


Route::controller(FaqsController::class)
->prefix('faqs')
->group(function(){

    Route::get('/', 'index');
});