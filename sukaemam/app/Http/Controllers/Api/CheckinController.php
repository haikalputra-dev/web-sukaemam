<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\User;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function testMethod(Request $request)
    {
        return response()->json(['message' => 'Check-in berhasil']);
    }
    public function store(Request $request)
    {
        try {
            $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'qr_code_data'  => 'required',
            ]);
            $user = User::first();
            if (! $user) {
                return response()->json(['message' => 'No user found'], 404);
            }

            $already = Checkin::where('user_id', $user->id)
                ->where('restaurant_id', $request->restaurant_id)
                ->whereDate('checkin_time', now())
                ->exists();

            if ($already) {
                return response()->json(['message' => 'Sudah check-in hari ini'], 400);
            }

            $checkin = Checkin::create([
                'user_id'       => $user->id,
                'restaurant_id' => $request->restaurant_id,
                'checkin_time'  => now(),
                'qr_code_data'  => $request->qr_code_data ?? $request->restaurant_id,
                'points_earned' => 10,
            ]);
            $user->increment('total_points', 10);

            return response()->json(['message' => 'Check-in berhasil', 'checkin' => $checkin]);
        } catch (\Exception $e) {
            \Log::error('CheckinController@store error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

}
