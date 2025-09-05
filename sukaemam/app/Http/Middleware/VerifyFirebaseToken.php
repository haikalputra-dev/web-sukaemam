<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\FirebaseAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class VerifyFirebaseToken
{
    public function __construct(private FirebaseAuthService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Ambil bearer token
        $authz = (string) $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $authz, $m)) {
            return $this->unauthorized('Missing bearer token');
        }
        $idToken = trim($m[1]);

        // Verifikasi token ke Google (RS256)
        try {
            $info = $this->auth->verify($idToken);
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid token: '.$e->getMessage());
        }

        // Ambil info dasar dari claims
        $uid    = (string) Arr::get($info, 'uid');
        $email  = Arr::get($info, 'email');
        $name   = Arr::get($info, 'name');
        $avatar = Arr::get($info, 'picture');

        // Normalisasi minimal
        $emailNorm = $email ? Str::lower(trim($email)) : null;
        $name = $name ?: ($emailNorm ? Str::before($emailNorm, '@') : 'User');

        // ====== AUTO-LINK BY EMAIL (tanpa bikin duplikat) ======
        // Urutan:
        // 1) Cari user berdasarkan firebase_uid (paling utama)
        // 2) Kalau belum ada & ada email → cari user lama berdasarkan email (case-insensitive)
        // 3) Kalau ketemu user email → "link" dengan mengisi firebase_uid
        // 4) Kalau tidak ada keduanya → buat user baru

        // 1) by firebase_uid
        $user = User::where('firebase_uid', $uid)->first();

        // 2) by email (kalau uid belum ketemu & email tersedia)
        if (!$user && $emailNorm) {
            // case-insensitive compare
            $user = User::whereRaw('LOWER(email) = ?', [$emailNorm])->first();
        }

        if ($user) {
            // 3) update/link
            $user->forceFill([
                'firebase_uid' => $uid,
                'name'         => $name ?? $user->name,
                // simpan email hanya jika kolom email kamu nullable/unik
                'email'        => $emailNorm ?? $user->email,
                // kalau punya kolom avatar, buka komentar di bawah:
                // 'avatar_url' => $avatar ?? $user->avatar_url,
            ])->save();
        } else {
            // 4) create baru
            $user = User::create([
                'firebase_uid' => $uid,
                'name'         => $name,
                'email'        => $emailNorm,   // boleh null untuk anon
                'total_points' => 0,
            ]);
        }

        // Set user pada request & auth()
        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);

        return $next($request);
    }

    private function unauthorized(string $msg): Response
    {
        return response()->json([
            'message' => 'Unauthenticated',
            'errors'  => ['auth' => [$msg]],
        ], 401);
    }
}
