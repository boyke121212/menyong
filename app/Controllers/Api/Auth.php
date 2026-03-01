<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Deden;

class Auth extends ResourceController
{
    public function refreshToken()
    {
        try {
            $algorithm = getenv('hash') ?: 'HS256';

            // 1. Ambil Authorization header
            $authHeader = $this->request->getHeaderLine('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return $this->failUnauthorized('Refresh token tidak ditemukan');
            }

            $refreshToken = trim(substr($authHeader, 7));

            // 2. Decode REFRESH TOKEN (JWT2)
            $payload = JWT::decode(
                $refreshToken,
                new Key(getenv('JWT2'), $algorithm)
            );

            if (!isset($payload->uid)) {
                return $this->failUnauthorized('Payload refresh token tidak valid');
            }

            // 3. Ambil user
            $deden = new Deden();
            $user  = $deden->find($payload->uid);

            if (!$user || ($user['status'] ?? '') !== 'active') {
                return $this->failUnauthorized('User tidak aktif');
            }
            $storedRefreshToken = (string) ($user['refresh_token'] ?? '');
            $incomingRefreshTokenHash = hash('sha256', $refreshToken);
            $isRefreshTokenMatch = $storedRefreshToken !== '' && (
                hash_equals($storedRefreshToken, $incomingRefreshTokenHash)
                || hash_equals($storedRefreshToken, $refreshToken) // backward compatibility token lama (raw)
            );

            if (!$isRefreshTokenMatch) {
                return $this->failUnauthorized('Token sudah dicabut');
            }

            // 4. DEVICE BINDING (WAJIB)
            $deviceHash = $this->request->getHeaderLine('X-Device-Hash');
            $appSig     = $this->request->getHeaderLine('X-App-Signature');

            if (
                !$deviceHash ||
                !$appSig ||
                ($user['device_hash'] ?? '') !== $deviceHash ||
                ($user['app_signature'] ?? '') !== $appSig
            ) {
                return $this->failUnauthorized('Device tidak valid');
            }

            // 5. Buat ACCESS TOKEN BARU (JWT1)
            $now = time();
            $accessPayload = [
                'uid' => $user['userId'],
                'iat' => $now,
                'exp' => $now + (int) getenv('expire1') // access token (short)
            ];

            $newAccessToken = JWT::encode(
                $accessPayload,
                getenv('JWT1'),
                $algorithm
            );

            // 6. Rotasi REFRESH TOKEN BARU (JWT2)
            $refreshPayload = [
                'uid' => $user['userId'],
                'iat' => $now,
                'exp' => $now + (int) getenv('expire2') // refresh token (long)
            ];

            $newRefreshToken = JWT::encode(
                $refreshPayload,
                getenv('JWT2'),
                $algorithm
            );

            // 6.5 SIMPAN KE DATABASE (INI YANG KURANG)
            $deden->update($user['userId'], [
                'access_token'  => hash('sha256', $newAccessToken),
                'refresh_token' => hash('sha256', $newRefreshToken),
                'device_hash'   => $deviceHash,
                'app_signature' => $appSig
            ]);

            // 7. Response JSON
            return $this->respond([
                'status'        => 'ok',
                'access_token'  => $newAccessToken,
                'refresh_token' => $newRefreshToken
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'REFRESH TOKEN ERROR: ' . $e->getMessage());
            return $this->failUnauthorized('Refresh token tidak valid');
        }
    }
}
