<?php

namespace App\Http\Middleware\v1;

use App\Services\v1\FileNonceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyStream
{
    public function __construct(
        private FileNonceService $fileNonceService
    ) {
        // 
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $file = $request->route('file');
        $encryptedNonce = $request->query('nonce');
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Check if the URL has a valid signature, ignoring the 'expires' parameter.
        $hasValidSignature = $request->hasValidSignatureWhileIgnoring(['expires']);
        abort_if(!$hasValidSignature, 403, 'Invalid or expired URL.');

        $nonce = $this->fileNonceService->decryptNonce($encryptedNonce);
        abort_if(!$nonce, 403, 'Invalid nonce provided.');
        
        $nonceData = $this->fileNonceService->getNonceData($nonce);
        abort_if(!$nonceData, 403, 'Nonce no longer exists.');

        $isNonceValid = $this->fileNonceService->verifyNonce($file, $ipAddress, $userAgent, $nonceData);
        abort_if(!$isNonceValid, 403, 'Nonce verification failed.');

        // Refresh nonce to extend its validity for active streaming sessions
        $this->fileNonceService->refreshNonce($nonce, $nonceData);

        return $next($request);
    }
}
