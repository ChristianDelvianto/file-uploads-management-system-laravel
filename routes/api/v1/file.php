<?php

use App\Http\Controllers\v1\File\FileController;
use App\Http\Controllers\v1\File\FileContentDownloadController;
use App\Http\Controllers\v1\File\FileContentShowController;
use App\Http\Controllers\v1\File\FileContentStreamController;
use App\Http\Controllers\v1\File\FileContentThumbnailController;
use App\Http\Controllers\v1\File\FileLinkDownloadController;
use App\Http\Controllers\v1\File\FileLinkShareController;
use App\Http\Controllers\v1\File\FileLinkStreamController;
use App\Http\Controllers\v1\File\FileNameController;
use App\Http\Controllers\v1\File\FileRemergeController;
use App\Http\Controllers\v1\File\FileRestoreController;
use App\Http\Controllers\v1\File\FileTrashController;
use App\Http\Controllers\v1\File\FileVisibilityController;
use Illuminate\Support\Facades\Route;

Route::apiResource('file', FileController::class)
->middlewareFor(['destroy'], ['auth'])
->only(['destroy', 'show']);

Route::prefix('file/{file}/content')
->as('file.content.')
->group(function () {
    Route::get('/', FileContentShowController::class)
    ->middleware(['verify_token'])
    ->withTrashed()
    ->name('show');

    Route::get('download', FileContentDownloadController::class)
    ->middleware(['verify_token'])
    ->name('download');

    // Only for audio and video files (If file deleted, throws 404)
    Route::get('stream', FileContentStreamController::class)
    ->middleware(['verify_token'])
    ->name('stream');

    // Apply withTrashed() so end users can see the file thumbnail in "trash" page
    Route::get('thumbnail', FileContentThumbnailController::class)
    ->withTrashed()
    ->name('thumbnail');
});

Route::prefix('file/{file}/link')
->as('file.link.')
->group(function () {
    // If file is public, guest can download
    Route::get('download', FileLinkDownloadController::class)->name('download');

    Route::get('share', FileLinkShareController::class)
    ->middleware(['auth'])
    ->name('share');

    // Only for audio and video files (If file is public, guest can stream)
    Route::get('stream', FileLinkStreamController::class)->name('stream');
});

Route::patch('file/{file}/name', FileNameController::class)
->middleware(['auth'])
->name('file.name');

Route::post('file/{file}/remerge', FileRemergeController::class)
->middleware(['auth'])
->name('file.remerge');

Route::patch('file/{file}/restore', FileRestoreController::class)
->middleware(['auth'])
->withTrashed()
->name('file.restore');

Route::patch('file/{file}/trash', FileTrashController::class)
->middleware(['auth'])
->withTrashed()
->name('file.trash');

Route::patch('file/{file}/visibility', FileVisibilityController::class)
->middleware(['auth'])
->name('file.visibility');
