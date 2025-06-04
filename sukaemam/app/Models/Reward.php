<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name', 'description', 'type', 'point_cost', 
        'restaurant_id', 'is_active', 'image_url'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'point_cost' => 'integer',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function userRewards()
    {
        return $this->hasMany(UserReward::class);
    }
}
