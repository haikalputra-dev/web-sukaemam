<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Restaurant;
use App\Services\GamificationService;
use App\Support\QrSignature;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckinController extends Controller
{
    /**
     * The gamification service instance.
     * @var \App\Services\GamificationService
     */
    protected $gamificationService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\GamificationService  $gamificationService
     * @return void
     */
    public function __construct(GamificationService $gamificationService)
    {
        // Menggunakan dependency injection pada constructor agar service
        // tersedia untuk semua method di dalam class ini.
        $this->gamificationService = $gamificationService;
    }

    /**
     * Handle a user check-in request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'qr'       => ['required', 'string', 'max:512'],
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        // Simpan level lama untuk mengecek apakah ada kenaikan level nanti
        $oldLevel = $user->level;

        // Validasi QR, restoran, signature, dan batas check-in harian
        $restaurant = $this->validateCheckinPrerequisites($data, $user);

        // Validasi lokasi (Geofence)
        $distance = $this->validateLocation($data, $restaurant);
        $radius = (int) env('CHECKIN_RADIUS_METERS', 75);

        // --- MULAI LOGIKA UTAMA ---

        // Gunakan konstanta dari service agar nilainya terpusat
        $pointsEarned = GamificationService::POINTS_FOR_CHECK_IN;

        $checkin = DB::transaction(function () use ($user, $restaurant, $data, $pointsEarned) {
            // 1. Buat record check-in
            $c = Checkin::create([
                'user_id'       => $user->id,
                'restaurant_id' => $restaurant->id,
                'qr_code_data'  => $data['qr'],
                'points_earned' => $pointsEarned,
                'checkin_time'  => now(),
                'day_key'       => (int) Carbon::now(config('app.timezone', 'UTC'))->format('Ymd'),
                'scan_lat'      => $data['lat'],
                'scan_lng'      => $data['lng'],
                'scan_accuracy' => $data['accuracy'] ?? null,
            ]);

            // 2. Panggil service untuk melakukan SEMUANYA:
            // tambah poin, hitung level, dan cek badge dalam satu panggilan.
            $this->gamificationService->addPointsForAction($user, 'check_in');

            return $c;
        });

        // --- SELESAI LOGIKA UTAMA ---

        // Ambil data user terbaru setelah semua proses di service selesai
        $user->refresh();
        $newLevel = $user->level;

        // Kembalikan response yang sama seperti sebelumnya
        return response()->json([
            'ok'              => true,
            'points_earned'   => $pointsEarned,
            'total_points'    => $user->total_points,
            'distance_m'      => round($distance),
            'radius_m'        => $radius,
            'checkin_id'      => $checkin->id,
            'restaurant_id'   => $restaurant->id,
            'restaurant_name' => $restaurant->username, // Pastikan kolom ini benar 'username' atau 'name'
            'level_up'        => $newLevel > $oldLevel,
            'current_level'   => $newLevel,
        ]);
    }

    /**
     * Validate QR, signature, and daily limits.
     *
     * @param  array  $data
     * @param  \App\Models\User  $user
     * @return \App\Models\Restaurant
     */
    private function validateCheckinPrerequisites(array $data, $user): Restaurant
    {
        // Parse QR
        $parts = explode('|', $data['qr']);
        if (count($parts) !== 5 || $parts[0] !== 'SE-V1') {
            throw ValidationException::withMessages(['qr' => 'Format QR tidak valid']);
        }
        [, $rid, $ts, $nonce, $sig] = $parts;

        // Validate Restaurant
        $restaurant = Restaurant::find($rid);
        if (!$restaurant || $restaurant->latitude === null || $restaurant->longitude === null) {
            throw ValidationException::withMessages(['qr' => 'Restoran tidak ditemukan atau lokasi belum diatur.']);
        }

        // Validate Signature
        $msg    = "SE-V1|{$rid}|{$ts}|{$nonce}";
        $expect = QrSignature::sign($msg, env('CHECKIN_HMAC_SECRET', ''));
        if (!hash_equals($expect, $sig)) {
            throw ValidationException::withMessages(['qr' => 'Signature tidak valid']);
        }

        // Validate Daily Limit
        $todayKey = (int) Carbon::now(config('app.timezone', 'UTC'))->format('Ymd');
        $alreadyCheckedIn = Checkin::query()
            ->where('user_id', $user->id)
            ->where('restaurant_id', $restaurant->id)
            ->where('day_key', $todayKey)
            ->exists();

        if ($alreadyCheckedIn) {
            throw ValidationException::withMessages(['limit' => 'Anda sudah check-in di restoran ini hari ini.']);
        }

        return $restaurant;
    }

    /**
     * Validate user's location against the restaurant's location (Geofence).
     *
     * @param  array  $data
     * @param  \App\Models\Restaurant  $restaurant
     * @return float The distance in meters.
     */
    private function validateLocation(array $data, Restaurant $restaurant): float
    {
        $radius = (int) env('CHECKIN_RADIUS_METERS', 75);

        $distance = $this->distanceMeters(
            (float) $data['lat'], (float) $data['lng'],
            (float) $restaurant->latitude, (float) $restaurant->longitude
        );

        if ($distance > $radius) {
            throw ValidationException::withMessages([
                'location' => "Di luar radius lokasi resto (jarak ~".round($distance)." m, batas {$radius} m)",
            ]);
        }

        return $distance;
    }


    /**
     * Calculate distance between two points using Haversine formula.
     *
     * @return float Distance in meters.
     */
    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0; // Earth radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * asin(min(1, sqrt($a)));
        return $R * $c;
    }
}
