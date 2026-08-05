<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\FileContentController;
use App\Http\Controllers\v1\FileLinkController;
use App\Http\Controllers\v1\FileRestoreController;
use App\Http\Controllers\v1\FileTrashController;
use App\Http\Controllers\v1\FileUpdateNameController;
use App\Http\Controllers\v1\FileUpdateVisibilityController;
use Illuminate\Support\Facades\Route;

Route::apiResource('file', FileController::class)
->middlewareFor(['destroy'], ['auth:sanctum'])
->only(['destroy', 'show']);

Route::prefix('file/{file}/content')
->as('file.content.')
->group(function () {
    Route::get('/', [FileContentController::class, 'show'])
    ->withoutMiddleware(['throttle:api'])
    ->middleware(['verify_token'])
    ->withTrashed()
    ->name('show');

    Route::get('download', [FileContentController::class, 'download'])
    ->middleware(['verify_token'])
    ->name('download');

    // Only for audio and video files
    Route::get('stream', [FileContentController::class, 'stream'])
    ->middleware(['verify_token'])
    ->name('stream');

    Route::get('thumbnail', [FileContentController::class, 'showThumbnail'])
    ->withTrashed()
    ->name('thumbnail');
});

Route::prefix('file/{file}/link')
->as('file.link.')
->group(function () {
    Route::get('download', [FileLinkController::class, 'download'])
    ->name('download');

    Route::get('share', [FileLinkController::class, 'share'])
    ->middleware(['auth:sanctum'])
    ->name('share');

    // Only for audio and video files
    Route::get('stream', [FileLinkController::class, 'stream'])
    ->name('stream');
});

Route::patch('file/{file}/restore', FileRestoreController::class)
->middleware(['auth:sanctum'])
->withTrashed()
->name('file.restore');

Route::patch('file/{file}/trash', FileTrashController::class)
->middleware(['auth:sanctum'])
->withTrashed()
->name('file.trash');

Route::prefix('file/{file}/update')
->as('file.update.')
->middleware(['auth:sanctum'])
->group(function () {
    Route::put('name', FileUpdateNameController::class)
    ->name('name');

    Route::put('visibility', FileUpdateVisibilityController::class)
    ->name('visibility');
});
