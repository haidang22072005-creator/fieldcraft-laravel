<?php

namespace App\Services;

use App\Models\MatchdayCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchdayCampaignService
{
    public function updateStatus(MatchdayCampaign $campaign, string $status, int $actor): MatchdayCampaign
    {
        $allowed = match ($campaign->status) { 'draft' => ['cancelled', 'scheduled'], 'scheduled' => ['active', 'cancelled', 'ended'], 'active' => ['ended', 'cancelled'], default => [] };
        if (! in_array($status, $allowed, true)) throw ValidationException::withMessages(['status' => 'Chiến dịch không thể chuyển trạng thái theo quy trình.']);
        if ($status === 'scheduled' && ! $campaign->approved_at) throw ValidationException::withMessages(['status' => 'Chiến dịch phải được admin phê duyệt trước khi lên lịch.']);
        return DB::transaction(function () use ($campaign, $status, $actor): MatchdayCampaign {
            $locked = MatchdayCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $locked->update(['status' => $status]);
            return $locked->fresh(['teamProfile', 'coupon', 'products']);
        });
    }
}
