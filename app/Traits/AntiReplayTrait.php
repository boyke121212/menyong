<?php

namespace App\Traits;

use CodeIgniter\HTTP\IncomingRequest;

trait AntiReplayTrait
{
    protected function validateAntiReplay(\CodeIgniter\HTTP\IncomingRequest $request): bool
    {
        $timestampRaw = trim($request->getPost('__ts') ?? '');
        $nonce        = trim($request->getPost('__nonce') ?? '');

        log_message('error', "ANTI-REPLAY POST TS={$timestampRaw} NONCE={$nonce}");

        if (!ctype_digit($timestampRaw) || empty($nonce)) {
            return false;
        }

        $timestamp = (int) $timestampRaw;
        $now = time();

        // toleransi 5 menit
        if (abs($now - $timestamp) > 300) {
            return false;
        }

        $uid = $_SESSION['uidnya'] ?? 'guest';
        $deviceHash = $request->getHeaderLine('X-Device-Hash') ?? 'unknown';

        $key = 'nonce_' . hash('sha256', $uid . '|' . $deviceHash . '|' . $nonce);

        $throttler = service('throttler');

        if ($throttler->check($key, 1, 120) === false) {
            return false;
        }

        return true;
    }
}