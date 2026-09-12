<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
}
