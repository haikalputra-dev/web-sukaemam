<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantImage extends Model
{
    use HasFactory;

    protected $fillable = ['restaurant_id', 'image_url', 'uploaded_at','is_main'];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_main' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
