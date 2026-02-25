<?php

use Illuminate\Support\Facades\Route;

Route::as('api.')
->group(function () {
    require __DIR__ . '/api/admin.php';
    require __DIR__ . '/api/v1/index.php';
});
