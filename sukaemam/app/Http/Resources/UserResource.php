<?php

namespace App\Http\Resources;

use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Hitung total poin dari semua check-in
        // $totalPoints = $this->checkIns->sum('points_earned');
        $this->loadMissing(['reviews', 'badges']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar,

            // 2. Ambil data langsung dari kolom di tabel users (lebih efisien)
            'total_points' => (int) $this->total_points,
            'level' => (int) $this->level,
            'total_reviews' => (int) $this->reviews->count(),
            'total_badges' => (int) $this->badges->count(),

            // 4. Format dan sertakan semua badge yang dimiliki user
            'badges' => BadgeResource::collection($this->whenLoaded('badges')),
        ];
    }
}
