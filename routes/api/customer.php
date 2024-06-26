<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::controller(CustomerController::class)
    ->prefix('customers')
    ->as('customer.')
    ->namespace("\App\Http\Controllers")
    ->middleware([
        'auth:sanctum',
        'can:premium,App\Models\Customer',
    ])
    ->group(function () {
        Route::get('/', 'index')->name('index');

        Route::get('/download', 'download')->name('download');

        Route::get('/{customer}', 'show')->name('show')->middleware('can:view,customer');
    });
