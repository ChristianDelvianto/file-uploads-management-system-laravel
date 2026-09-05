<?php

namespace App\Http\Middleware\v1;

use App\Services\v1\TokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class VerifyToken
{
    public function __construct(
        protected TokenService $tokenService
    ) {
        // 
    }

    /**
     * Handle an incoming request.
     * 
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $file = $request->route('file');
        $token = $request->query('token', null);

        if (!URL::hasCorrectSignature($request, false)) {
            abort(403, 'Invalid access URL.');
        }

        if (blank($token) || is_null($token)) {
            abort(403, 'Access token is required.');
        }

        if (preg_match('/^[a-z0-9]{64}$/i', $token) === 0) {
            abort(403, 'Invalid access token.');
        }

        $tokenData = $this->tokenService->getTokenData($token);

        if (is_null($tokenData)) {
            abort(403, 'Access token has expired.');
        }

        $isTokenValid = $this->tokenService->verifyToken($file, $request->ip(), $request->userAgent(), $tokenData);

        if (!$isTokenValid) {
            abort(403, 'Invalid access token.');
        }

        // Manage sliding lifecycle states for media streaming
        if ($this->tokenService->isStreamable($file)) {
            $this->tokenService->extendTokenLife($token, $tokenData);
        } else {
            $this->tokenService->invalidateToken($token);
        }

        return $next($request);
    }
}
