<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Restaurant;
use App\Support\QrSignature; // kelas helper b64url + sign (yang kemarin)
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckinController extends Controller
{
    public function testMethod(Request $request)
    {
        return response()->json(['message' => 'Check-in berhasil']);
    }

    //     public function store(Request $request): JsonResponse
    // {
    //     $qr = (string) $request->input('qr', '');
    //     if ($qr === '') {
    //         return response()->json(['status' => 'error', 'message' => 'QR missing'], 422);
    //     }

    //     // TODO: validasi isi QR kamu (misal format RESTO-123|ts|nonce, cek expiry/nonce di DB, dsb)
    //     $points = 15;

    //     return response()->json([
    //         'status' => 'ok',
    //         'points' => $points,
    //         'qr' => $qr,
    //     ]);
    // }

    public function store(Request $request)
    {
        $data = $request->validate([
            'qr'       => ['required','string','max:512'],
            'lat'      => ['required','numeric','between:-90,90'],
            'lng'      => ['required','numeric','between:-180,180'],
            'accuracy' => ['nullable','numeric','min:0'], // meter
        ]);

        // TODO: ganti ke Firebase middleware → $user = $request->user();
        $user = $request->user();
        if (!$user) {
            throw ValidationException::withMessages(['auth' => 'Unauthenticated']);
        }

        // Parse QR: SE-V1|rid|ts|nonce|sig
        $parts = explode('|', $data['qr']);
        if (count($parts) !== 5 || $parts[0] !== 'SE-V1') {
            throw ValidationException::withMessages(['qr' => 'Format QR tidak valid']);
        }
        [, $rid, $ts, $nonce, $sig] = $parts;

        if (!ctype_digit((string)$rid) || !ctype_digit((string)$ts)) {
            throw ValidationException::withMessages(['qr' => 'Payload QR tidak valid']);
        }
        $rid = (int) $rid;

        // Cek resto ada
        $restaurant = Restaurant::query()->find($rid);
        if (!$restaurant || $restaurant->latitude === null || $restaurant->longitude === null) {
            throw ValidationException::withMessages(['qr' => 'Restoran tidak ditemukan / lokasi resto belum diisi']);
        }

        // Verifikasi signature (tanpa expiry karena QR dicetak)
        $msg    = "SE-V1|{$rid}|{$ts}|{$nonce}";
        $expect = QrSignature::sign($msg, env('CHECKIN_HMAC_SECRET', ''));
        if (!hash_equals($expect, $sig)) {
            throw ValidationException::withMessages(['qr' => 'Signature tidak valid']);
        }

        // Geofence
        $radius     = (int) env('CHECKIN_RADIUS_METERS', 75);
        $maxAcc     = (float) env('CHECKIN_MAX_ACCURACY_METERS', 60);
        $acc        = $data['accuracy'] ?? null;

        if ($acc !== null && $acc > $maxAcc) {
            throw ValidationException::withMessages(['location' => "Akurasi lokasi terlalu besar (> {$maxAcc} m)"]);
        }

        $distance = $this->distanceMeters(
            (float) $data['lat'],
            (float) $data['lng'],
            (float) $restaurant->latitude,
            (float) $restaurant->longitude
        );

        if ($distance > $radius) {
            throw ValidationException::withMessages([
                'location' => "Di luar radius lokasi resto (jarak ~".round($distance)." m, batas {$radius} m)",
            ]);
        }

        // 1x per HARI
        $tz = config('app.timezone', 'UTC');
        $todayKey = (int) Carbon::now($tz)->format('Ymd');

        if (filter_var(env('CHECKIN_ONE_PER_DAY_GLOBAL', false), FILTER_VALIDATE_BOOLEAN)) {
            // Global: user cuma boleh 1x check-in dimana pun per hari
            $exists = Checkin::query()
                ->where('user_id', $user->id)
                ->where('day_key', $todayKey)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'limit' => 'Sudah melakukan check-in hari ini.',
                ]);
            }
        } else {
            // Per resto: 1x per restoran per hari
            $exists = Checkin::query()
                ->where('user_id', $user->id)
                ->where('restaurant_id', $rid)
                ->where('day_key', $todayKey)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'limit' => 'Sudah melakukan check-in di restoran ini hari ini.',
                ]);
            }
        }

        // Simpan check-in + tambah poin (dinamis nanti)
        $points = 15; // TODO: ambil aturan dinamis per resto
        $checkin = DB::transaction(function () use ($user, $rid, $data, $points, $todayKey) {
            $c = Checkin::create([
                'user_id'       => $user->id,
                'restaurant_id' => $rid,
                'qr_code_data'  => $data['qr'],      // boleh disimpan untuk audit (TIDAK unik)
                'points_earned' => $points,
                'checkin_time'  => now(),
                'day_key'       => $todayKey,
                'scan_lat'      => $data['lat'],
                'scan_lng'      => $data['lng'],
                'scan_accuracy' => $data['accuracy'] ?? null,
            ]);
            $user->increment('total_points', $points);
            return $c;
        });

        return response()->json([
            'ok' => true,
            'points_earned' => $points,
            'total_points'  => $user->fresh()->total_points,
            'distance_m'    => round($distance),
            'radius_m'      => $radius,
            'checkin_id'    => $checkin->id,
        ]);
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        $c = 2 * asin(min(1, sqrt($a)));
        return $R * $c;
    }

    // public function store(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'restaurant_id' => 'required|exists:restaurants,id',
    //             'qr_code_data'  => 'required',
    //         ]);
    //         $user = User::first();
    //         if (! $user) {
    //             return response()->json(['message' => 'No user found'], 404);
    //         }

    //         $already = Checkin::where('user_id', $user->id)
    //             ->where('restaurant_id', $request->restaurant_id)
    //             ->whereDate('checkin_time', now())
    //             ->exists();

    //         if ($already) {
    //             return response()->json(['message' => 'Sudah check-in hari ini'], 400);
    //         }

    //         $checkin = Checkin::create([
    //             'user_id'       => $user->id,
    //             'restaurant_id' => $request->restaurant_id,
    //             'checkin_time'  => now(),
    //             'qr_code_data'  => $request->qr_code_data ?? $request->restaurant_id,
    //             'points_earned' => 10,
    //         ]);
    //         $user->increment('total_points', 10);

    //         return response()->json(['message' => 'Check-in berhasil', 'checkin' => $checkin]);
    //     } catch (\Exception $e) {
    //         \Log::error('CheckinController@store error: ' . $e->getMessage());
    //         return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    //     }
    // }

}
