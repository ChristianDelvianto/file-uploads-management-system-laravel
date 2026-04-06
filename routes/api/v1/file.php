<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\FileContentController;
use App\Http\Controllers\v1\FileLogController;
use Illuminate\Support\Facades\Route;

Route::apiResource('files', FileController::class)
    ->middlewareFor(['restore', 'store', 'update'], ['auth:sanctum', 'role:user'])
    ->only(['restore', 'show', 'store', 'update']);

Route::apiResource('files.logs', FileLogController::class)
    ->middlewareFor(['index'], ['auth:sanctum', 'role:user'])
    ->only(['index', 'store']);

Route::delete('files/{uuid}', [FileController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:user'])
    ->name('files.destroy');

Route::patch('files/{uuid}/restore', [FileController::class, 'restore'])
    ->middleware(['auth:sanctum', 'role:user'])
    ->name('files.restore');

// Serve content
Route::get('files/{file}/{storageName}', [FileContentController::class, 'showContent'])
    ->middleware(['signed'])
    ->name('files.content');
// Route::get('files/{file}/deleted/{storageName}', [FileContentController::class, 'showDeletedContent'])
//     ->middleware(['signed'])
//     ->name('files.content.deleted');

// Thumbnail
Route::get('files/{file}/thumbnail/{thumbnailName}', [FileContentController::class, 'showThumbnail'])
    ->middleware(['signed'])
    ->name('files.thumbnail');
// Route::get('files/{file}/deleted/thumbnail/{thumbnailName}', [FileContentController::class, 'showDeletedThumbnail'])
//     ->middleware(['signed'])
//     ->name('files.thumbnail.deleted');

// Download link
Route::get('files/{file}/download/link', [FileContentController::class, 'generateDownloadLink'])
    ->name('files.download');

// Download content
Route::get('files/{file}/download/{storageName}', [FileContentController::class, 'downloadContent'])
    ->middleware(['signed'])
    ->name('files.download.content');