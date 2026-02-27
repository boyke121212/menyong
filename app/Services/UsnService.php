<?php

namespace App\Services;

use App\Models\Deden;
use App\Models\Dauo;
use Config\Services;

class UsnService
{
    protected Deden $userModel;

    public function __construct()
    {
        $this->userModel = new Deden();
        $this->dauo = new Dauo();
    }

    public function authThis(string $username, string $password): ?array
    {
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        unset($user['password']);
        return $user;
    }

    public function updateToken(string $username, string $token): bool
    {
        return $this->userModel
            ->where('username', $username)
            ->set(['token' => $token])
            ->update();
    }

    public function getSecondToken(string $token1)
    {
        if (! session()->get('isLoggedIn')) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }
        $client = Services::curlrequest();

        try {
            $response = $client->post(
                base_url('security/proketsi2'), // sesuaikan route kamu
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token1
                    ],
                    'http_errors' => false
                ]
            );

            $statusCode = $response->getStatusCode();
            $body       = json_decode($response->getBody(), true);

            if ($statusCode !== 200) {
                return [
                    'status'  => 'error',
                    'message' => $body['message'] ?? 'Gagal mengambil token kedua'
                ];
            }

            return $body;
        } catch (\Throwable $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function authToken(string $token)
    {

        if (! session()->get('isLoggedIn')) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }
        $client = Services::curlrequest();

        try {
            $response = $client->post(
                base_url('security/proketsi1'), // sesuaikan route
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'http_errors' => false
                ]
            );

            $statusCode = $response->getStatusCode();
            $body       = json_decode($response->getBody(), true);

            if ($statusCode !== 200) {
                return [
                    'status'  => 'error',
                    'message' => $body['message'] ?? 'Token tidak valid'
                ];
            }

            return $body;
        } catch (\Throwable $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}