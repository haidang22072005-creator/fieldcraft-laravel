<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\AdminNotificationService;
use App\Services\CustomerSegmentService;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function index(Request $request, LoyaltyService $loyalty, CustomerSegmentService $segments): JsonResponse
    {
        $customers = $loyalty->customerMetrics()->latest()->paginate((int) min(100, $request->integer('per_page', 20)));
        $customers->getCollection()->transform(function (User $user) use ($loyalty, $segments): array {
            $metrics = $user->only(['completed_spend', 'completed_order_count', 'last_completed_purchase', 'loyalty_points']);
            $profile = $loyalty->profileFromMetrics($metrics);
            return array_merge($user->only(['id', 'name', 'email', 'phone']), ['loyalty' => $profile, 'segments' => $segments->fromMetrics($metrics, $profile['tier'])]);
        });
        return response()->json($customers);
    }
    public function show(User $user, LoyaltyService $loyalty, CustomerSegmentService $segments): JsonResponse
    {
        abort_unless($user->role === 'customer', 404);
        return response()->json(['data' => ['customer' => $user, 'loyalty' => $loyalty->profile($user), 'segments' => $segments->for($user)]]);
    }

    public function segments(Request $request, CustomerSegmentService $segments): JsonResponse
    {
        $loyalty = app(LoyaltyService::class);
        $customers = $loyalty->customerMetrics()->latest()->paginate((int) min(100, $request->integer('per_page', 20)));
        $customers->getCollection()->transform(function (User $user) use ($loyalty, $segments): array {
            $metrics = $user->only(['completed_spend', 'completed_order_count', 'last_completed_purchase', 'loyalty_points']);
            $profile = $loyalty->profileFromMetrics($metrics);
            return ['customer' => $user, 'loyalty' => $profile, 'segments' => $segments->fromMetrics($metrics, $profile['tier'])];
        });
        if ($request->segment) $customers->setCollection($customers->getCollection()->filter(fn (array $row) => in_array($request->segment, $row['segments'], true))->values());
        return response()->json($customers);
    }

    public function issueVoucher(Request $request, User $user, ActivityLogService $activity, AdminNotificationService $notifications): JsonResponse
    {
        abort_unless($user->role === 'customer', 404);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:coupons,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1', 'max:100000000'],
            'minimum_order_value' => ['nullable', 'integer', 'min:0'],
            'max_discount' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['type'] === 'percent' && (int) $data['value'] > 100) {
            return response()->json(['message' => 'Phần trăm giảm giá không được vượt quá 100.'], 422);
        }

        $manualCode = $data['code'] ?? null;
        $code = strtoupper($manualCode ?? '');
        if ($manualCode !== null && Coupon::query()->whereRaw('UPPER(code) = ?', [$code])->exists()) {
            return response()->json(['message' => 'Mã voucher đã tồn tại.'], 422);
        }
        while ($code === '' || Coupon::query()->where('code', $code)->exists()) {
            $code = 'FC-'.strtoupper(str()->random(10));
        }

        $voucher = Coupon::query()->create([
            ...$data,
            'code' => $code,
            'user_id' => $user->id,
            'minimum_order_value' => $data['minimum_order_value'] ?? 0,
            'max_discount' => $data['type'] === 'percent' ? ($data['max_discount'] ?? null) : null,
            'is_active' => $data['is_active'] ?? true,
            'usage_limit' => $data['usage_limit'] ?? 1,
            'per_user_limit' => $data['per_user_limit'] ?? 1,
        ]);

        $activity->record('customer.voucher_issued', $voucher, ['customer_id' => $user->id, 'type' => $voucher->type, 'value' => $voucher->value], $request->user()->id);
        $notifications->notifyUserOnce($user, 'customer_voucher_issued', 'Bạn nhận được voucher mới', $voucher->code, ['coupon_id' => $voucher->id], $voucher);

        return response()->json(['data' => $voucher], 201);
    }
}
