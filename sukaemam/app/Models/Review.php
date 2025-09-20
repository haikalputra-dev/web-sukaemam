<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'checkin_id',
        'rating',
        'comment',
        'photo_url'
    ];

    protected $casts = ['rating' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    protected static function booted()
    {
        static::saved(function ($review) {
            $review->restaurant->updateAverageRating();
        });
    }

        public function checkin()
    {
        return $this->belongsTo(Checkin::class);
    }
}
