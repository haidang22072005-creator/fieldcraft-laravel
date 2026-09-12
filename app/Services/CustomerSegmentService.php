<?php

namespace App\Services;

use App\Models\User;

class CustomerSegmentService
{
    public function for(User $user): array
    {
        $completed = $user->orders()->where('status', 'completed')->latest('created_at')->get(['id', 'created_at']);
        $count = $completed->count();
        $last = $completed->first()?->created_at;
        $segments = [];
        if ($count <= 1) $segments[] = 'khách mới';
        if ($count >= 2) $segments[] = 'khách quay lại';
        if (in_array(app(LoyaltyService::class)->profile($user)['tier'], ['PRO', 'FIELDCRAFT ELITE'], true)) $segments[] = 'VIP';
        if ($last && $last->lt(now()->subDays((int) config('services.loyalty.inactive_days', 180)))) $segments[] = 'lâu chưa mua';
        return $segments;
    }
}
