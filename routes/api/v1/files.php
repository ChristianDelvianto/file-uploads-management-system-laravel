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

// File activities
Route::apiResource('files.activities', FileActivityController::class)
->middlewareFor(['index'], ['auth:sanctum', 'role:user'])
->only(['index', 'store']);

// Route::apiResource('files.reports', FileReportController::class)
// ->middlewareFor([], [])
// ->only([]);

Route::prefix('files/{uuid}')
->as('files.')
->group(function () {
    Route::middleware(['auth:sanctum', 'role:user'])
    ->group(function () {
        Route::delete('/', [FileController::class, 'destroyPermanently'])
        ->name('destroy');

        Route::match(['put', 'patch'], 'restore', [FileController::class, 'restore'])
        ->name('restore');

        Route::match(['put', 'patch'], 'trash', [FileController::class, 'trash'])
        ->name('trash');

        Route::prefix('update')
        ->as('update.')
        ->group(function () {
            Route::match(['put', 'patch'], 'name', [FileController::class, 'updateName'])
            ->name('name');

            Route::match(['put', 'patch'], 'visibility', [FileController::class, 'updateVisibility'])
            ->name('visibility');
        });
    });

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

        Route::prefix('trashed')
        ->as('trashed.')
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

        // Route::put('share', [FileLinkController::class, ''])->middleware(['auth:sanctum', 'role:user'])
        // ->name('');
    });
});
