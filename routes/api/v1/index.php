<?php

use App\Http\Controllers\v1\FileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
->as('v1.')
->group(function () {
    require __DIR__ . '/auth.php';
    require __DIR__ . '/user.php';

    Route::apiResource('files', FileController::class)
        ->middlewareFor(['destroy', 'store', 'update'], ['auth:sanctum', 'role:user'])
        ->except(['index']);

    Route::get('files/{file}/content', [FileController::class, 'showContent'])
        ->name('files.content');

    Route::get('files/{file}/thumbnail', [FileController::class, 'showThumbnail'])
        ->name('files.thumbnail');
});
