<?php

namespace App\Http\Middleware\v1;

use App\Services\v1\FileNonceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNonce
{
    public function __construct(
        public FileNonceService $fileNonceService
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
        $encryptedNonce = $request->query('nonce');

        $data = $this->fileNonceService->getNonceData($encryptedNonce);

        if (is_null($data)) {
            abort(403, 'Nonce has no data.');
        } else if (!$this->fileNonceService->verifyNonce($request->route('file'), $encryptedNonce, $request->userAgent(), $request->ip(), $data)) {
            abort(403, 'Invalid nonce.');
        }

        if ($data['one_time_access']) {
            $this->fileNonceService->removeNonce(hash('sha256', $data['nonce']));
        } else if ($data['should_refresh']) {
            $this->fileNonceService->refreshNonce($encryptedNonce, $data);
        }

        return $next($request);
    }
}
