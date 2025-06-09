<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'description',
        'latitude',
        'longitude',
        'average_rating',
    ];

    // Virtual attribute for form handling
    protected $appends = ['restaurant_images'];

    public function getRestaurantImagesAttribute()
    {
        // Load the relationship if it hasn't been loaded yet
        if (!$this->relationLoaded('restaurantImages')) {
            $this->load('restaurantImages');
        }
        
        return $this->getRelation('restaurantImages')->pluck('image_url')->toArray();
    }

    public function setRestaurantImagesAttribute($value)
    {
        // This will be handled in the CreateRestaurant and EditRestaurant pages
    }

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'average_rating' => 'float',
    ];

    // Relationships
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function restaurantImages(): HasMany
    {
        return $this->hasMany(RestaurantImage::class);
    }

    // Helper methods
    public function getQrCodeData(): string
    {
        return (string) $this->id;
    }

    public function updateAverageRating(): void
    {
        $averageRating = $this->reviews()->avg('rating') ?? 0;
        $this->update(['average_rating' => round($averageRating, 1)]);
    }

    // Scopes
    public function scopeNearby($query, float $latitude, float $longitude, float $radiusKm = 10)
    {
        return $query->selectRaw(
            "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$latitude, $longitude, $latitude]
        )->having('distance', '<', $radiusKm)->orderBy('distance');
    }

    public function scopeHighRated($query, float $minRating = 4.0)
    {
        return $query->where('average_rating', '>=', $minRating);
    }
}