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
        $customers = User::query()->where('role', 'customer')->withCount(['orders as completed_order_count' => fn ($q) => $q->where('status', 'completed')])->latest()->paginate((int) min(100, $request->integer('per_page', 20)));
        $customers->getCollection()->transform(fn (User $user) => array_merge($user->only(['id', 'name', 'email', 'phone']), ['loyalty' => $loyalty->profile($user), 'segments' => $segments->for($user)]));
        return response()->json($customers);
    }
    public function show(User $user, LoyaltyService $loyalty, CustomerSegmentService $segments): JsonResponse
    {
        abort_unless($user->role === 'customer', 404);
        return response()->json(['data' => ['customer' => $user, 'loyalty' => $loyalty->profile($user), 'segments' => $segments->for($user)]]);
    }

    public function segments(Request $request, CustomerSegmentService $segments): JsonResponse
    {
        $customers = User::query()->where('role', 'customer')->get()->map(fn (User $user) => ['customer' => $user, 'segments' => $segments->for($user)])->filter(fn (array $row) => ($request->segment ? in_array($request->segment, $row['segments'], true) : true))->values();
        return response()->json(['data' => $customers]);
    }
}
