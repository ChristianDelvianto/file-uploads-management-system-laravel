<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->statefulApi()
            ->throttleWithRedis()
            ->alias([
                'verify_token' => \App\Http\Middleware\v1\VerifyToken::class
            ])
            ->api(
                [],
                [
                    \App\Http\Middleware\ForceJsonResponseMiddleware::class
                ],
                [],
                [],
            )->redirectGuestsTo(function (Request $request): JsonResponse {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
