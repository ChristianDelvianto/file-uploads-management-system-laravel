<?php

namespace App\Http\Middleware\v1;

use App\Services\v1\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyToken
{
    public function __construct(
        protected TokenService $tokenService
    ) {

    }

    /**
     * Handle an incoming media stream request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $file = $request->route('file');

        $tokenString = $request->query('token');

        abort_if(blank($tokenString), 403, 'Access token missing.');

        // Fetch token metadata from Redis
        $tokenData = $this->tokenService->getTokenData($tokenString);
        
        /**
         * STATE 1: First-time entry playback optimization loop.
         * If the token isn't in cache yet, this is the very first browser page click.
         * We strictly validate the Signed URL signature block here.
         */
        if (is_null($tokenData)) {
            abort_if(!$request->hasValidSignature(), 403, 'Streaming signature expired.');
            
            // Register and bind the stateless token payload footprint
            $tokenString = $this->tokenService->generateToken($file, $request->ip(), $request->userAgent());
            $tokenData = $this->tokenService->getTokenData($tokenString);
        }

        /**
         * STATE 2: Continuous Media Streaming (HTML5 Video Partial Chunks)
         * If the token exists, bypass time-locked URL checks completely.
         * Evaluate the browser footprint variables directly.
         */
        $isTokenValid = $this->tokenService->verifyToken($file, $request->ip(), $request->userAgent(), $tokenData);
        abort_if(!$isTokenValid, 403, 'Token verification failed.');

        // Manage sliding lifecycle states for media streaming vs standard downloads
        if ($this->tokenService->isFileStreamable($file)) {
            $this->tokenService->extendTokenLife($tokenString, $tokenData);
        } else {
            $this->tokenService->invalidateToken($tokenString);
        }

        return $next($request);
    }
}
