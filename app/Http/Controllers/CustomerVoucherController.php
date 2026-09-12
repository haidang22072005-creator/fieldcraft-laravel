<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerVoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $now = now();
        $vouchers = Coupon::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $now))
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->where(function ($query) use ($request): void {
                $query->whereNull('per_user_limit')->orWhereRaw('(SELECT COUNT(*) FROM coupon_usages WHERE coupon_usages.coupon_id = coupons.id AND coupon_usages.user_id = ?) < coupons.per_user_limit', [$request->user()->id]);
            })
            ->latest()
            ->paginate(min(50, max(1, $request->integer('per_page', 20))));

        return response()->json($vouchers);
    }
}
