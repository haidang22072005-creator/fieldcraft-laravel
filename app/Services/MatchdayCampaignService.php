<?php

namespace App\Services;

use App\Models\MatchdayCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchdayCampaignService
{
    public function updateStatus(MatchdayCampaign $campaign, string $status, int $actor): MatchdayCampaign
    {
        return DB::transaction(function () use ($campaign, $status, $actor): MatchdayCampaign {
            $locked = MatchdayCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $allowed = match ($locked->status) {
                'draft' => ['cancelled', 'scheduled'],
                'scheduled' => ['active', 'cancelled', 'ended'],
                'active' => ['ended', 'cancelled'],
                default => [],
            };
            if (! in_array($status, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'Chiến dịch không thể chuyển trạng thái theo quy trình.']);
            }
            if ($status === 'scheduled' && ! $locked->approved_at) {
                throw ValidationException::withMessages(['status' => 'Chiến dịch phải được admin phê duyệt trước khi lên lịch.']);
            }
            if ($status === 'active') {
                if (! $locked->approved_at) {
                    throw ValidationException::withMessages(['status' => 'Chiến dịch phải được admin phê duyệt trước khi kích hoạt.']);
                }
                if ($locked->starts_at && $locked->starts_at->isFuture()) {
                    throw ValidationException::withMessages(['status' => 'Chiến dịch chưa đến thời điểm bắt đầu.']);
                }
                if ($locked->ends_at && $locked->ends_at->isPast()) {
                    throw ValidationException::withMessages(['status' => 'Chiến dịch đã hết hạn và không thể kích hoạt.']);
                }
            }
            $locked->update(['status' => $status]);
            return $locked->fresh(['teamProfile', 'coupon', 'products']);
        });
    }
}
