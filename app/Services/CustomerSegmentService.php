<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class CustomerSegmentService
{
    public function for(User $user): array
    {
        $metrics = $user->orders()->where('status', 'completed')->selectRaw('COUNT(*) AS completed_order_count, MAX(created_at) AS last_completed_purchase')->first();
        $tier = app(LoyaltyService::class)->profileFromMetrics((array) $metrics->getAttributes())['tier'];
        return $this->fromMetrics((array) $metrics->getAttributes(), $tier);
    }

    public function fromMetrics(array $metrics, ?string $tier = null): array
    {
        $count = (int) ($metrics['completed_order_count'] ?? 0);
        $last = $metrics['last_completed_purchase'] ?? null;
        $segments = [];
        if ($count <= 1) $segments[] = 'khách mới';
        if ($count >= 2) $segments[] = 'khách quay lại';
        if (in_array($tier, ['PRO', 'FIELDCRAFT ELITE'], true)) $segments[] = 'VIP';
        if ($last && Carbon::parse($last)->lt(now()->subDays((int) config('services.loyalty.inactive_days', 180)))) $segments[] = 'lâu chưa mua';
        return $segments;
    }
}
