<?php
// app/Models/Restaurant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'address',
        'description',
        'latitude',
        'longitude',
        'average_rating',
        'image_url',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'average_rating' => 'decimal:2',
    ];

    // Relationships
    public function checkins()
    {
        return $this->hasMany(CheckIn::class);
    }

    public function images()
    {
        return $this->hasMany(RestaurantImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    // Helper methods
    public function updateAverageRating(): void
    {
        $averageRating = $this->reviews()->avg('rating');
        $this->update(['average_rating' => round($averageRating, 2)]);
    }

    public function getTotalCheckinsAttribute(): int
    {
        return $this->checkins()->count();
    }

    public function getQrCodeDataAttribute(): string
    {
        return $this->id; // QR code contains restaurant ID
    }
}