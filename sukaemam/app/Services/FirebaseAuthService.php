<?php
// File: app/Services/FirebaseAuthService.php

namespace App\Services;

// 1. Ganti 'use' statement yang lama dengan ini
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Lcobucci\JWT\Token; // Untuk type-hinting yang lebih baik
use RuntimeException;

class FirebaseAuthService
{
    // 2. Gunakan Constructor Injection. Laravel akan secara otomatis membuat dan
    //    mengirimkan object FirebaseAuth yang sudah terkonfigurasi dari config/firebase.php
    public function __construct(private FirebaseAuth $auth) {}

    /**
     * Verifikasi ID token dan kembalikan data user sebagai array.
     */
    public function verify(string $idToken): array
    {
        try {
            /** @var Token $verifiedToken */
            $verifiedToken = $this->auth->verifyIdToken($idToken, $checkIfRevoked = true);
        } catch (FailedToVerifyToken $e) {
            // Tangkap error spesifik dari library
            throw new RuntimeException('Token tidak valid: ' . $e->getMessage());
        }

        $claims = $verifiedToken->claims();

        // Kembalikan data user dari token
        return [
            'uid'     => $claims->get('sub'),
            'email'   => $claims->get('email'),
            'name'    => $claims->get('name'),
            'picture' => $claims->get('picture'),
        ];
    }
}
