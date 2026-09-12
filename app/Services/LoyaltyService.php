<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

class LoyaltyService
{
    public function profile(User $user): array
    {
        $spend = (int) $user->orders()->where('status', 'completed')->sum('total');
        $thresholds = collect(config('services.loyalty.thresholds', []))->mapWithKeys(fn ($value, $tier) => [strtoupper((string) $tier) => (int) $value])->sort()->all();
        $current = 'ROOKIE';
        $currentThreshold = 0;
        $nextTier = null;
        $nextThreshold = null;
        foreach ($thresholds as $tier => $threshold) {
            if ($spend >= $threshold) { $current = $tier; $currentThreshold = $threshold; }
            elseif ($nextTier === null) { $nextTier = $tier; $nextThreshold = $threshold; }
        }
        $progress = $nextThreshold === null ? 100 : (int) min(100, max(0, round(($spend - $currentThreshold) / max(1, $nextThreshold - $currentThreshold) * 100)));
        return ['tier' => $current, 'completed_spend' => $spend, 'current_threshold' => $currentThreshold, 'next_tier' => $nextTier, 'next_threshold' => $nextThreshold, 'amount_to_next_tier' => $nextThreshold === null ? 0 : max(0, $nextThreshold - $spend), 'progress_percent' => $progress];
    }
}
