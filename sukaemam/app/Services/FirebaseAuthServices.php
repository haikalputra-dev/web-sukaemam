<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseAuthService
{
    private string $projectId;
    private int $clockSkew;

    public function __construct()
    {
        $this->projectId = (string) env('FIREBASE_PROJECT_ID', '');
        $this->clockSkew = (int) env('FIREBASE_CLOCK_SKEW', 60);
        if ($this->projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID belum diset.');
        }
    }

    /**
     * Verifikasi ID token dan kembalikan claims terstruktur.
     *
     * @throws \Throwable bila token invalid.
     */
    public function verify(string $idToken): array
    {
        [$headerB64, $payloadB64, $sigB64] = explode('.', $idToken) + [null, null, null];
        if (!$headerB64 || !$payloadB64 || !$sigB64) {
            throw new RuntimeException('Format JWT tidak valid');
        }

        $header = json_decode($this->b64urlDecode($headerB64), true, 512, JSON_THROW_ON_ERROR);
        $kid = $header['kid'] ?? null;
        $alg = $header['alg'] ?? null;
        if ($alg !== 'RS256' || !$kid) {
            throw new RuntimeException('JWT header tidak valid (alg/kid)');
        }

        $certPem = $this->getGoogleCertPem($kid); // ambil cert publik berdasar kid

        // Atur leeway untuk iat/nbf/exp
        JWT::$leeway = $this->clockSkew;

        // Decode & verifikasi tanda tangan
        /** @var object $decoded */
        $decoded = JWT::decode($idToken, new Key($certPem, 'RS256'));
        $claims = (array) $decoded;

        // Validasi klaim utama
        $expectedIss = "https://securetoken.google.com/{$this->projectId}";
        if (($claims['aud'] ?? null) !== $this->projectId) {
            throw new RuntimeException('audience tidak cocok');
        }
        if (($claims['iss'] ?? null) !== $expectedIss) {
            throw new RuntimeException('issuer tidak cocok');
        }
        $sub = (string) ($claims['sub'] ?? '');
        if ($sub === '') {
            throw new RuntimeException('sub/uid kosong');
        }

        // Normalisasi output
        return [
            'uid'             => $sub,
            'user_id'         => $claims['user_id'] ?? $sub,       // Firebase menaruh keduanya
            'email'           => $claims['email'] ?? null,
            'email_verified'  => (bool) ($claims['email_verified'] ?? false),
            'name'            => $claims['name'] ?? null,
            'picture'         => $claims['picture'] ?? null,
            'firebase'        => (array) ($claims['firebase'] ?? []), // sign_in_provider, identities, etc
            'claims_raw'      => $claims,
        ];
    }

    private function getGoogleCertPem(string $kid): string
    {
        $url = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

        // cache sesuai max-age header Google (biasanya 1 jam). fallback 55 menit.
        $certs = Cache::remember('google_idp_x509', 55 * 60, function () use ($url) {
            $resp = Http::timeout(5)->get($url);
            $resp->throw();
            return $resp->json();
        });

        $pem = $certs[$kid] ?? null;

        // Jika kid tidak ditemukan (rotasi kunci), refresh paksa sekali
        if (!$pem) {
            $resp = Http::timeout(5)->get($url);
            $resp->throw();
            $fresh = $resp->json();
            Cache::put('google_idp_x509', $fresh, 55 * 60);
            $pem = $fresh[$kid] ?? null;
        }

        if (!$pem) {
            throw new RuntimeException('Public key untuk kid tidak ditemukan');
        }

        return $pem;
    }

    private function b64urlDecode(string $b64url): string
    {
        $b64 = strtr($b64url, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) $b64 .= str_repeat('=', 4 - $pad);
        return base64_decode($b64, true) ?: '';
    }
}
