<?php
// app/Http/Resources/RestaurantResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mainImage = $this->restaurantImages->firstWhere('is_main', true);
        $fallbackImage = $this->restaurantImages->first();
        $mainImageObject = $this->restaurantImages->firstWhere('is_main', true)
                    ?? $this->restaurantImages->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'shortAddress' => $this->address,
            'description' => $this->description,
            'rating' => (float) $this->average_rating,
            'distance_km' => $this->whenNotNull($this->distance, round($this->distance, 1)),
            'reviewCount' => $this->whenLoaded('reviews', fn() => $this->reviews->count()),
            'priceInfo' => $this->price_info,
            'mainImageUrl' => $mainImageObject ? asset('storage/' . $mainImageObject->image_url) : null,
            'galleryImageUrls' => $this->restaurantImages->map(fn($image) => asset('storage/' . $image->image_url)),
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'isRecommended' => (bool) $this->is_recommended,
        ];
    }
}
