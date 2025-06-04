<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'avatar_url',
        'level',
        'total_points',
        'firebase_uid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'level' => 'integer',
        'total_points' => 'integer',
    ];

    // Relationships
    public function checkins()
    {
        return $this->hasMany(CheckIn::class);
    }

    public function userBadges()
    {
        return $this->hasMany(UserBadge::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
                    ->withPivot('earned_at')
                    ->withTimestamps();
    }

    public function userRewards()
    {
        return $this->hasMany(UserReward::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Helper methods
    public function addPoints(int $points): void
    {
        $this->increment('total_points', $points);
        $this->checkLevelUp();
    }

    private function checkLevelUp(): void
    {
        $newLevel = intval($this->total_points / 1000) + 1; // Level up every 1000 points
        if ($newLevel > $this->level) {
            $this->update(['level' => $newLevel]);
        }
    }

    public function canClaimReward(Reward $reward): bool
    {
        return $this->total_points >= $reward->point_cost;
    }
}