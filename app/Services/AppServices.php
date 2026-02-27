<?php

namespace App\Services;

use App\Models\Deden;
use Firebase\JWT\JWT;

class AppServices
{
    protected Deden $userModel;

    public function __construct()
    {
        $this->userModel = new Deden();
    }

    public function login(string $username, string $password): array
    {
        $user = $this->userModel
            ->where('username', $username)
            ->first();

        if (!$user) {
            throw new \Exception('User Ridak Ditemukan');
        }

        if (!password_verify($password, $user['password'])) {
            throw new \Exception('password salah');
        }

        if ($user['roleId'] == '1') {
            throw new \Exception('Akun ini tidak Bisa Login di Applikasi');
        }

        if ($user['status'] !== 'belum') {
            throw new \Exception('Anda sudah Login sebelum nya,Silahkan Hubungi Admin');
        }

        return [
            'userId'  => $user["userId"],
            'access_token'  => $this->generateAccessToken($user),
            'refresh_token' => $this->generateRefreshToken($user),
        ];
    }

    private function generateAccessToken(array $user): string
    {
        $now = time();

        $payload = [
            'iat'  => $now,
            'exp'  => $now + getenv('expire1'), // access token (1 jam)
            'uid'  => $user['userId'],
            'role' => $user['roleId'],
            'type' => 'access',
        ];

        return JWT::encode($payload, getenv('JWT1'), getenv('hash'));
    }

    private function generateRefreshToken(array $user): string
    {
        $now = time();

        $payload = [
            'iat'  => $now,
            'exp'  => $now + getenv('expire2'), // refresh token (7 hari)
            'uid'  => $user['userId'],
            'type' => 'refresh',
        ];

        return JWT::encode($payload, getenv('JWT2'), getenv('hash'));
    }
}