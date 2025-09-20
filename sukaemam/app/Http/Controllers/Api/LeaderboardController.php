<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validasi input periode, bisa 'weekly' atau 'monthly'. Defaultnya 'weekly'.
        $period = $request->input('period', 'weekly');
        $limit = $request->input('limit', 20); // Ambil top 20 user

        // Tentukan rentang waktu berdasarkan periode yang dipilih
        if ($period === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
        } else {
            $startDate = Carbon::now()->startOfWeek(Carbon::MONDAY); // Minggu dimulai hari Senin
        }
        $endDate = Carbon::now();

        // [DIUBAH] Query sekarang mengambil dari 'point_transactions'
        $leaderboard = DB::table('point_transactions')
            ->join('users', 'point_transactions.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.username as name',
                'users.avatar_url',
                'users.level',
                'users.instagram_username',
                'users.tiktok_username',
                'users.facebook_profile_url',
                // [DIUBAH] SUM dari kolom 'points' di tabel baru
                DB::raw('CAST(SUM(point_transactions.points) AS SIGNED) as period_points')
            )
            // [DIUBAH] Filter tanggal dari tabel baru
            ->whereBetween('point_transactions.created_at', [$startDate, $endDate])
            ->groupBy(
                'users.id',
                'users.username',
                'users.avatar_url',
                'users.level',
                'users.instagram_username',
                'users.tiktok_username',
                'users.facebook_profile_url'
            )
            ->orderByDesc('period_points')
            ->limit($limit)
            ->get();

        return response()->json($leaderboard);
    }
}
