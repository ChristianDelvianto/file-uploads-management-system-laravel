<?php

use App\Http\Controllers\v1\UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
->as('user.')
->middleware(['auth:sanctum', 'role:user'])
->group(function () {
    Route::get('files', [UserDashboardController::class, 'files'])
    ->name('files');

    Route::get('shared', [UserDashboardController::class, 'sharedToUser'])
    ->name('shared');

    Route::prefix('trash')
    ->group(function () {
        Route::delete('/', [UserDashboardController::class, 'clearTrash'])
        ->name('trash.delete');

        Route::get('/', [UserDashboardController::class, 'trash'])
        ->name('trash');
    });
});
