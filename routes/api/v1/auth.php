<?php

use App\Http\Controllers\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
->as('auth.')
->group(function () {
    Route::get('info', [AuthController::class, 'userInfo'])
    ->middleware(['auth:sanctum'])
    ->name('info');

    Route::post('new', [AuthController::class, 'newUser'])
    ->name('new');

    Route::prefix('tokens')
    ->as('tokens.')
    ->group(function () {
        Route::delete('/', [AuthController::class, 'revokeCurrentToken'])
        ->middleware(['auth:sanctum'])
        ->name('delete');

        Route::post('/', [AuthController::class, 'newToken'])
        ->name('new');
    });
});
