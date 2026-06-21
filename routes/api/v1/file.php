<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\FileActivityController;
use App\Http\Controllers\v1\FileContentController;
use App\Http\Controllers\v1\FileLinkController;
use App\Http\Controllers\v1\FileRestoreController;
use App\Http\Controllers\v1\FileTrashController;
use App\Http\Controllers\v1\FileUpdateNameController;
use App\Http\Controllers\v1\FileUpdateVisibilityController;
use Illuminate\Support\Facades\Route;

Route::apiResource('file', FileController::class)
->middlewareFor(['destroy'], ['auth:sanctum', 'role:user'])
->only(['destroy', 'show']);

Route::prefix('file/{file}/content')
->as('file.content.')
->group(function () {
    Route::get('/', [FileContentController::class, 'show'])
    ->withoutMiddleware(['throttle:api'])
    ->middleware(['verify_nonce'])
    ->withTrashed()
    ->name('show');

    Route::get('download', [FileContentController::class, 'download'])
    ->middleware(['verify_nonce'])
    ->name('download');

    // Only for audio and video files
    Route::get('stream', [FileContentController::class, 'stream'])
    ->middleware(['verify_nonce'])
    ->name('stream');

    Route::get('thumbnail', [FileContentController::class, 'showThumbnail'])
    ->withoutMiddleware(['throttle:api'])
    ->withTrashed()
    ->name('thumbnail');
});

Route::prefix('file/{file}/link')
->as('file.link.')
->group(function () {
    Route::get('download', [FileLinkController::class, 'download'])
    ->name('download');

    Route::get('share', [FileLinkController::class, 'share'])
    ->middleware(['auth:sanctum', 'role:user'])
    ->name('share');

    // Only for audio and video files
    Route::get('stream', [FileLinkController::class, 'stream'])
    ->name('stream');
});

Route::patch('file/{file}/restore', FileRestoreController::class)
->middleware(['auth:sanctum', 'role:user'])
->name('file.restore');

Route::patch('file/{file}/trash', FileTrashController::class)
->middleware(['auth:sanctum', 'role:user'])
->withTrashed()
->name('file.trash');

Route::prefix('file/{file}/update')
->as('file.update.')
->middleware(['auth:sanctum', 'role:user'])
->group(function () {
    Route::put('name', FileUpdateNameController::class)
    ->name('name');

    Route::put('visibility', FileUpdateVisibilityController::class)
    ->name('visibility');
});

Route::apiResource('file.activities', FileActivityController::class)
->middlewareFor(['index'], ['auth:sanctum', 'role:user'])
->withTrashed(['index'])
->only(['index', 'store']);
