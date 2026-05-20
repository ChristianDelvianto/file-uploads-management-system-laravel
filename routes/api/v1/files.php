<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\FileActivityController;
use App\Http\Controllers\v1\FileContentController;
use App\Http\Controllers\v1\FileLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('files/{file}')
->as('files.')
->group(function () {
    Route::get('/', [FileController::class, 'show'])
    ->name('show');

    Route::middleware(['auth:sanctum', 'role:user'])
    ->group(function () {
        // Permanent delete
        Route::delete('/', [FileController::class, 'destroyTrash'])
        ->name('destroy');

        Route::patch('restore', [FileController::class, 'restoreFromTrash'])
        ->name('restore');

        Route::patch('trash', [FileController::class, 'markAsTrash'])
        ->name('trash');

        Route::prefix('update')
        ->as('update.')
        ->group(function () {
            Route::put('name', [FileController::class, 'updateName'])
            ->name('name');

            Route::put('visibility', [FileController::class, 'updateVisibility'])
            ->name('visibility');
        });
    });

    // Serve file content and thumbnail
    Route::prefix('content')
    ->as('content.')
    ->group(function () {
        Route::get('/', [FileContentController::class, 'showContent'])
        ->middleware(['signed', 'verify_nonce'])
        ->name('main');

        Route::get('download', [FileContentController::class, 'downloadContent'])
        ->middleware(['signed', 'verify_nonce'])
        ->name('download');

        // Only for audio/video files
        Route::get('stream', [FileContentController::class, 'streamContent'])
        ->middleware(['verify_stream'])
        ->name('stream');

        Route::get('thumbnail', [FileContentController::class, 'showThumbnail'])
        ->middleware(['signed', 'verify_nonce'])
        ->name('thumbnail');
    });

    Route::prefix('link')
    ->as('link.')
    ->group(function () {
        Route::get('download', [FileLinkController::class, 'download'])
        ->name('download');

        Route::get('share', [FileLinkController::class, 'share'])
        ->middleware(['auth:sanctum', 'role:user'])
        ->name('share');

        Route::get('stream', [FileLinkController::class, 'content'])
        ->name('stream');
    });
});

Route::apiResource('files.activities', FileActivityController::class)
->middlewareFor(['index'], ['auth:sanctum', 'role:user'])
->only(['index', 'store']);
