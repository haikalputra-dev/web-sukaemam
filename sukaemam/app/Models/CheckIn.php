<?php
// app/Models/CheckIn.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CheckIn extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'checkins';

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'checkin_time',
        'qr_code_data',
        'points_earned',
        'day_key',
        'scan_lat',
        'scan_lng',
        'scan_accuracy',
    ];

    protected $casts = [
        'checkin_time' => 'datetime',
        'points_earned' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    // Boot method for automatic actions
    protected static function booted()
    {
        static::created(function ($checkin) {
            // Auto-award points when check-in is created
            $checkin->user->addPoints($checkin->points_earned);
        });
    }

    // Helper methods
    public static function createCheckin(User $user, Restaurant $restaurant, string $qrCodeData): self
    {
        $points = self::calculatePoints($user, $restaurant);

        return self::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'checkin_time' => now(),
            'qr_code_data' => $qrCodeData,
            'points_earned' => $points,
        ]);
    }

    private static function calculatePoints(User $user, Restaurant $restaurant): int
    {
        $basePoints = 100;

        // Bonus for first visit to this restaurant
        $isFirstVisit = !CheckIn::where('user_id', $user->id)
                              ->where('restaurant_id', $restaurant->id)
                              ->exists();

        return $isFirstVisit ? $basePoints + 50 : $basePoints;
    }

        public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
