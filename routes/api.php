<?php

use Illuminate\Support\Facades\Route;

Route::group([], function () {
    \App\Helpers\Routes\RouteHelpers::includeRouteFiles(__DIR__.'/api');
});
