<?php

use App\Http\Controllers\v1\UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
->as('user.')
->middleware(['auth:sanctum', 'role:user'])
->group(function () {
    Route::get('home', [UserDashboardController::class, 'home'])->name('home');
    Route::get('images', [UserDashboardController::class, 'images'])->name('images');
    Route::get('videos', [UserDashboardController::class, 'videos'])->name('videos');
    Route::get('audios', [UserDashboardController::class, 'audios'])->name('audios');
    Route::get('documents', [UserDashboardController::class, 'documents'])->name('documents');
    Route::get('others', [UserDashboardController::class, 'others'])->name('others');
    Route::get('deleted', [UserDashboardController::class, 'deleted'])->name('deleted');
    Route::delete('delete-all', [UserDashboardController::class, 'deleteAll'])->name('delete-all');

    Route::get('logs', [UserDashboardController::class, 'logs'])->name('logs');
});
