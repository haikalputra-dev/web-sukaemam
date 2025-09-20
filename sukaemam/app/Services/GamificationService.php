<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\PointTransaction;

class GamificationService
{
    // --- BAGIAN BARU: DEFINISI POIN ---
    const POINTS_FOR_CHECK_IN = 20;
    const POINTS_FOR_REVIEW = 25;
    const POINTS_FOR_SOCIAL_SHARE = 15;
    const POINTS_FOR_NEW_BADGE = 40;
    // ------------------------------------

    const BASE_XP_PER_LEVEL = 30;

    /**
     * Metode utama untuk menambah poin dan memicu semua logika gamifikasi.
     *
     * @param User $user
     * @param string $action ('check_in', 'review', 'social_share', 'new_badge')
     * @return void
     */
    public function addPointsForAction(User $user, string $action): void
    {
        $pointsToAdd = 0;
        switch ($action) {
            case 'check_in':
                $pointsToAdd = self::POINTS_FOR_CHECK_IN;
                break;
            case 'review':
                $pointsToAdd = self::POINTS_FOR_REVIEW;
                break;
            case 'social_share':
                $pointsToAdd = self::POINTS_FOR_SOCIAL_SHARE;
                break;
            case 'new_badge':
                $pointsToAdd = self::POINTS_FOR_NEW_BADGE;
                break;
        }

        if ($pointsToAdd > 0) {
            PointTransaction::create([
                'user_id' => $user->id,
                'points'  => $pointsToAdd,
                'description'  => $action,
            ]);
            // 1. Tambah poin ke total poin user
            $user->total_points += $pointsToAdd;

            // 2. Hitung ulang level user berdasarkan poin baru
            $user->level = $this->calculateLevel($user->total_points);

            // 3. Simpan perubahan ke database
            $user->save();

            // 4. Setelah poin dan level diperbarui, cek apakah user berhak dapat badge baru
            $this->checkAndAwardBadges($user);
        }
    }

    // ... (metode calculateLevel, getTotalXpForLevel, getLevelProgress tidak berubah) ...
    public function calculateLevel(int $totalPoints): int
    {
        if ($totalPoints < self::BASE_XP_PER_LEVEL) {
            return 1;
        }
        $level = 1;
        while ($totalPoints >= $this->getTotalXpForLevel($level + 1)) {
            $level++;
        }
        return $level;
    }
    public function getTotalXpForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }
        $n = $level - 1;
        return (int) (($n / 2) * (2 * self::BASE_XP_PER_LEVEL + ($n - 1) * self::BASE_XP_PER_LEVEL));
    }
    public function getLevelProgress(int $totalPoints, int $currentLevel): array
    {
        $xpForCurrentLevel = $this->getTotalXpForLevel($currentLevel);
        $xpForNextLevel = $this->getTotalXpForLevel($currentLevel + 1);
        $xpInCurrentLevel = $totalPoints - $xpForCurrentLevel;
        $xpNeededForNextLevel = $xpForNextLevel - $xpForCurrentLevel;
        return [
            'current_xp' => $xpInCurrentLevel,
            'xp_for_next_level' => $xpNeededForNextLevel,
            'progress_percentage' => $xpNeededForNextLevel > 0 ? round(($xpInCurrentLevel / $xpNeededForNextLevel) * 100) : 100,
        ];
    }
    public function checkAndAwardBadges(User $user)
    {
        $this->awardLevelBadge($user);
        $this->awardAchievementBadges($user);
    }
    private function awardLevelBadge(User $user)
    {
        $levelBadges = [
            1 => 1, 6 => 2, 16 => 3, 26 => 4, 36 => 5, 51 => 6,
        ];
        $badgeIdToAward = null;
        foreach ($levelBadges as $levelRequired => $badgeId) {
            if ($user->level >= $levelRequired) {
                $badgeIdToAward = $badgeId;
            }
        }
        if ($badgeIdToAward) {
            $this->awardBadgeIfNotOwned($user, $badgeIdToAward);
        }
    }
    private function awardAchievementBadges(User $user)
    {
        if ($user->reviews()->exists()) {
            $this->awardBadgeIfNotOwned($user, 7);
        }
        if ($user->reviews()->whereNotNull('photo_url')->count() >= 5) {
            $this->awardBadgeIfNotOwned($user, 8);
        }
    }

    private function awardBadgeIfNotOwned(User $user, int $badgeId)
    {
        if (!$user->badges()->where('badge_id', $badgeId)->exists()) {
            $user->badges()->attach($badgeId, ['earned_at' => now()]);

            Log::info("User {$user->id} awarded badge {$badgeId}. Adding points.");

            $this->addPointsForAction($user, 'new_badge');
            // ---------------------------------------------
        }
    }
}
