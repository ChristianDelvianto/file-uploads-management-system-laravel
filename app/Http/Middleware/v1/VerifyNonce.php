<?php

namespace App\Http\Middleware\v1;

use App\Services\v1\FileNonceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNonce
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

        $nonce = $this->fileNonceService->decryptNonce($encryptedNonce);
        abort_if(!$nonce, 403, 'Invalid nonce provided.');

        $nonceData = $this->fileNonceService->getNonceData($nonce);
        abort_if(!$nonceData, 403, 'Nonce no longer exists.');

        $isValid = $this->fileNonceService->verifyNonce($file, $ipAddress, $userAgent, $nonceData);
        abort_if(!$isValid, 403, 'Nonce verification failed.');

        if ($nonceData['one_time_access']) {
            $this->fileNonceService->removeNonce($nonce);
        }

        return $next($request);
    }
}
