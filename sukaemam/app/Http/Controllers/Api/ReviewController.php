<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Checkin;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
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
        // Menggunakan dependency injection pada constructor (praktik terbaik)
        $this->gamificationService = $gamificationService;
    }

    /**
     * Store a newly created review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Checkin  $checkin
     * @return \App\Http\Resources\ReviewResource
     */
    public function store(Request $request, Checkin $checkin)
    {
        // 1. Validasi Keamanan: Pastikan pengguna yang login adalah pemilik check-in
        if ($checkin->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // 2. Validasi Aturan: Pastikan check-in ini belum pernah direview
        if ($checkin->review()->exists()) {
            return response()->json(['message' => 'Anda sudah memberikan review untuk check-in ini.'], 409);
        }

        // 3. Validasi Input dari aplikasi Flutter
        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:10', 'max:500'],
            // 'photo'   => ['nullable', 'image', 'max:2048'], // Untuk nanti jika ada upload foto
        ]);

        $user = $request->user();

        // 4. Gunakan Transaksi untuk memastikan konsistensi data
        $review = DB::transaction(function () use ($user, $checkin, $data) {
            // Buat record review baru
            $newReview = $checkin->review()->create([
                'user_id'       => $user->id,
                'restaurant_id' => $checkin->restaurant_id,
                'rating'        => $data['rating'],
                'comment'       => $data['comment'],
                // 'photo_url' => $photoPath ?? null, // Logika untuk menyimpan path foto
            ]);

            // Panggil service untuk melakukan SEMUANYA:
            // tambah poin review, cek & beri badge, dan tambah poin badge jika dapat.
            $this->gamificationService->addPointsForAction($user, 'review');

            return $newReview;
        });

        // 5. Kembalikan respons sukses menggunakan ReviewResource
        return new ReviewResource($review);
    }
}
