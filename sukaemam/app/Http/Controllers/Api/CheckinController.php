<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\User;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'qr_code_data'  => 'required', // validasi tambahan sesuai kebutuhan
        ]);
        // For testing purposes, use the first user
        $user = User::first();
        if (!$user) {
            return response()->json(['message' => 'No user found'], 404);
        }

        // Check for existing check-in today
        $already = Checkin::where('user_id', $user->id)
            ->where('restaurant_id', $request->restaurant_id)
            ->whereDate('checkin_time', now())
            ->exists();

        if ($already) {
            return response()->json(['message' => 'Sudah check-in hari ini'], 400);
        }

        // Simpan check-in
        try {
            $checkin = Checkin::create([
                'user_id'       => $user->id,
                'restaurant_id' => $request->restaurant_id,
                'checkin_time'  => now(),
                'qr_code_data'  => $request->qr_code_data ?? $request->restaurant_id,
                'points_earned' => 10,
            ]);
        } catch (\Exception $e) {
            \Log::error('Checkin GAGAL: ' . $e->getMessage());
            return response()->json(['message' => 'Insert gagal', 'error' => $e->getMessage()], 500);
        }

        // (Opsional) Update poin user
        $user->increment('total_points', 10);

        return response()->json(['message' => 'Check-in berhasil', 'checkin' => $checkin]);
    }
}
