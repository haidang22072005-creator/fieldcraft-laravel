<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Services\AbandonedCartService;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AbandonedCartController extends Controller
{
    public function index(AbandonedCartService $service): JsonResponse { return response()->json(['data' => $service->carts()]); }
    public function contacted(Request $request, Cart $cart, ActivityLogService $activity): JsonResponse
    {
        abort_unless($cart->user_id && $cart->items()->exists(), 422, 'Giỏ hàng không còn hợp lệ.');
        $data = $request->validate(['coupon_id' => ['nullable', 'exists:coupons,id']]);
        if (($data['coupon_id'] ?? null) && ! Coupon::query()->whereKey($data['coupon_id'])->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists()) throw ValidationException::withMessages(['coupon_id' => 'Mã giảm giá không còn hiệu lực.']);
        $cart->update(['contacted_at' => now(), 'contacted_by' => $request->user()->id, 'contact_coupon_id' => $data['coupon_id'] ?? null]);
        $activity->record('abandoned_cart.mark_contacted', $cart, ['coupon_id' => $data['coupon_id'] ?? null], $request->user()->id);
        return response()->json(['data' => $cart->fresh(['user', 'items.variant.product', 'contactCoupon'])]);
    }
}
