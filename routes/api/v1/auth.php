<?php

use App\Http\Controllers\v1\AuthAccountController;
use App\Http\Controllers\v1\AuthLoginController;
use App\Http\Controllers\v1\AuthLogoutController;
use App\Http\Controllers\v1\AuthSignupController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
->as('auth.')
->group(function () {
    Route::post('login', AuthLoginController::class)->name('login');
    Route::post('signup', AuthSignupController::class)->name('signup');

    Route::middleware(['auth'])
    ->group(function () {
        Route::get('account', AuthAccountController::class)->name('account');
        Route::post('logout', AuthLogoutController::class)->name('logout');
    });
});
