<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
->as('v1.')
->middleware(['throttle:api'])
->group(function () {
    require __DIR__ . '/auth.php';
    require __DIR__ . '/files.php';
    require __DIR__ . '/uploads.php';
    require __DIR__ . '/user.php';
});
