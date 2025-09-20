<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'message' => 'Ulasan berhasil disimpan!',
            'review_id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'points_earned' => 10, // Hardcode sesuai logika di controller
        ];
    }
}
