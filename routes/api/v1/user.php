<?php

use App\Http\Controllers\v1\UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
->as('user.')
->middleware(['auth:sanctum', 'role:user'])
->group(function () {
    Route::get('activities', [UserDashboardController::class, 'activities'])
    ->name('activities');

    Route::get('files', [UserDashboardController::class, 'files'])
    ->name('files');

    Route::prefix('trash')
    ->group(function () {
        Route::delete('/', [UserDashboardController::class, 'deleteTrashed'])
        ->name('trashed.delete');

        Route::get('/', [UserDashboardController::class, 'trashed'])
        ->name('trashed');
    });
});
