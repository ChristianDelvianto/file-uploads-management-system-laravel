<?php

use Illuminate\Support\Facades\Route;

Route::as('api.')
->middleware(['throttle:api'])
->group(function () {
    require __DIR__ . '/api/v1/index.php';
});
