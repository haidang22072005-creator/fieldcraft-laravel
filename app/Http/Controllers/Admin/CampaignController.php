<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchdayCampaign;
use App\Services\ActivityLogService;
use App\Services\AdminNotificationService;
use App\Services\MatchdayCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(): JsonResponse { return response()->json(['data' => MatchdayCampaign::with(['teamProfile', 'coupon', 'products', 'approver'])->latest()->get()]); }
    public function store(Request $request, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'team_profile_id' => ['nullable', 'exists:team_profiles,id'], 'coupon_id' => ['nullable', 'exists:coupons,id'], 'product_ids' => ['nullable', 'array'], 'product_ids.*' => ['integer', 'exists:products,id'], 'banner_path' => ['nullable', 'string', 'max:255'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $campaign = MatchdayCampaign::create(collect($data)->except('product_ids')->merge(['status' => 'draft'])->all());
        $campaign->products()->sync($data['product_ids'] ?? []);
        $activity->record('campaign.created', $campaign, [], $request->user()->id);
        return response()->json(['data' => $campaign->fresh(['teamProfile', 'coupon', 'products'])], 201);
    }
    public function show(MatchdayCampaign $campaign): JsonResponse { return response()->json(['data' => $campaign->load(['teamProfile', 'coupon', 'products', 'approver'])]); }
    public function approve(Request $request, MatchdayCampaign $campaign, ActivityLogService $activity, AdminNotificationService $notifications): JsonResponse
    {
        abort_unless($campaign->status === 'draft', 422, 'Chỉ chiến dịch nháp mới được phê duyệt.');
        $campaign->update(['approved_by' => $request->user()->id, 'approved_at' => now()]);
        $activity->record('campaign.approved', $campaign, [], $request->user()->id);
        $notifications->notify('campaign_approved', 'Chiến dịch đã được phê duyệt', $campaign->name, [], $campaign);
        return response()->json(['data' => $campaign->fresh(['teamProfile', 'coupon', 'products', 'approver'])]);
    }
    public function status(Request $request, MatchdayCampaign $campaign, MatchdayCampaignService $service, ActivityLogService $activity): JsonResponse
    {
        $status = $request->validate(['status' => ['required', 'in:draft,scheduled,active,ended,cancelled']])['status'];
        $updated = $service->updateStatus($campaign, $status, $request->user()->id);
        $activity->record('campaign.status_changed', $updated, ['status' => $status], $request->user()->id);
        return response()->json(['data' => $updated]);
    }
}
