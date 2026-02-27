<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Deden;

class MobileFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Token tidak ditemukan');
        }

        $token = trim(substr($authHeader, 7));

        try {
            // 🔑 Decode ACCESS TOKEN
            $payload = JWT::decode(
                $token,
                new Key(getenv('JWT1'), 'HS256')
            );

            if (!isset($payload->uid)) {
                throw new \Exception('Login Anda Ditolak');
            }

            // 👤 Ambil user
            $user = model(Deden::class)->find($payload->uid);

            if (!$user || ($user['status'] ?? '') !== 'active') {
                throw new \Exception('Silahkan Login');
            }

            // ❌ TOKEN SUDAH DICABUT OLEH ADMIN
            if (empty($user['access_token']) || $token !== $user['access_token']) {
                return $this->unauthorized('Checking Device');
            }

            // 🔐 DEVICE BINDING (WAJIB)
            $deviceHash = $request->getHeaderLine('X-Device-Hash');
            $appSig     = $request->getHeaderLine('X-App-Signature');

            if (
                !$deviceHash ||
                !$appSig ||
                $deviceHash !== ($user['device_hash'] ?? '') ||
                $appSig !== ($user['app_signature'] ?? '')
            ) {
                throw new \Exception('Device tidak valid');
            }

            // inject uid ke request (opsional)
         $request->uid = $payload->uid;
        } catch (\Throwable $e) {
            return $this->unauthorized(
                $e->getMessage() ?: 'Unauthorized'
            );
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // tidak perlu apa-apa
    }

    /**
     * Helper unauthorized response (WAJIB di Filter)
     */
    private function unauthorized(string $message)
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'status'   => 401,
                'error'    => 401,
                'messages' => [
                    'error' => $message
                ]
            ]);
    }
}
