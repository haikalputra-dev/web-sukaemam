<?php
// app/Http/Controllers/Api/RestaurantController.php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource; // <-- Import class Resource
use App\Models\Restaurant;                 // <-- Import class Model
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * Menampilkan daftar semua restoran.
     */
    public function index(Request $request)
        {

            $data = $request->validate([
                'lat' => ['nullable', 'numeric', 'between:-90,90'],
                'lng' => ['nullable', 'numeric', 'between:-180,180'],
                'recommended' => ['nullable', 'boolean'],
            ]);

            $query = Restaurant::query()->with(['restaurantImages', 'reviews']);

            if ($data['recommended'] ?? false) {
                $query->where('is_recommended', true);
            }

            // 3. Cek apakah ada koordinat yang dikirim
            if (isset($data['lat']) && isset($data['lng'])) {
                // Jika ada, panggil scope 'nearby' yang sudah Anda buat!
                // Angka 20 merepresentasikan radius pencarian dalam KM. Anda bisa sesuaikan.
                $query->nearby($data['lat'], $data['lng'], 20);
            } else {
                // Jika tidak ada koordinat, urutkan seperti biasa (misal: yang terbaru)
                $query->latest();
            }

            // 4. Eksekusi query dengan paginasi
            $restaurants = $query->paginate(10);

            // 5. Kembalikan hasilnya
            return RestaurantResource::collection($restaurants);
        }
}
