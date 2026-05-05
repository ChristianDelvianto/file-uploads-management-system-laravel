<?php

use App\Http\Controllers\v1\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('uploads')
->as('uploads.')
->middleware(['auth:sanctum'])
->group(function () {
    Route::post('/', [UploadController::class, 'store'])
    ->name('store');

    Route::prefix('{upload}')
    ->group(function () {
        Route::get('/', [UploadController::class, 'show'])
        ->name('show');

        Route::patch('cancel', [UploadController::class, 'cancel'])
        ->name('cancel');

        Route::patch('complete', [UploadController::class, 'complete'])
        ->name('complete');

        Route::post('chunks', [UploadController::class, 'storeChunk'])
        ->name('store.chunks');
    });
});
