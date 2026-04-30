<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\FileActivityController;
use App\Http\Controllers\v1\FileContentController;
use App\Http\Controllers\v1\FileLinkController;
use App\Http\Controllers\v1\FileTrashedContentController;
use Illuminate\Support\Facades\Route;

Route::apiResource('files', FileController::class)
->middlewareFor(['restore'], ['auth:sanctum', 'role:user'])
->only(['restore', 'show']);

Route::apiResource('files.activities', FileActivityController::class)
->middlewareFor(['index'], ['auth:sanctum', 'role:user'])
->only(['index', 'store']);

Route::prefix('files/{uuid}')
->as('files.')
->group(function () {
    Route::get('/', [FileController::class, 'show'])
    ->name('show');

    Route::middleware(['auth:sanctum', 'role:user'])
    ->group(function () {
        Route::delete('/', [FileController::class, 'destroyTrash'])
        ->name('destroy');

        Route::patch('restore', [FileController::class, 'restoreFromTrash'])
        ->name('restore');

        Route::patch('trash', [FileController::class, 'markAsTrash'])
        ->name('trash');

        // Update file's name and visibility
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
    ->middleware(['signed'])
    ->group(function () {
        Route::get('download/{storageName}', [FileContentController::class, 'downloadContent'])
        ->name('content');

        Route::get('main/{storageName}', [FileContentController::class, 'showContent'])
        ->name('main');

        Route::get('thumbnail/{thumbnailName}', [FileContentController::class, 'showThumbnail'])
        ->name('thumbnail');

        Route::prefix('trash')
        ->as('trash.')
        ->group(function () {
            Route::get('main/{storageName}', [FileTrashedContentController::class, 'showTrashedContent'])
            ->name('main');

            Route::get('thumbnail/{thumbnailName}', [FileTrashedContentController::class, 'showTrashedThumbnail'])
            ->name('thumbnail');
        });
    });

    Route::prefix('link')
    ->as('link.')
    ->group(function () {
        Route::get('content', [FileLinkController::class, 'content'])
        ->name('content');

        Route::get('download', [FileLinkController::class, 'download'])
        ->name('download');

        Route::put('share', [FileLinkController::class, 'share'])
        ->middleware(['auth:sanctum', 'role:user'])
        ->name('share');
    });
});
