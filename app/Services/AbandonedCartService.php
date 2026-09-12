<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Collection;

class AbandonedCartService
{
    public function carts(): Collection
    {
        $cutoff = now()->subHours((int) config('services.loyalty.abandoned_cart_hours', 24));
        return Cart::query()->whereNotNull('user_id')->whereRaw('COALESCE(last_activity_at, updated_at) < ?', [$cutoff])->whereHas('items')->with(['user', 'items.variant.product', 'contactCoupon'])->latest('updated_at')->get()->map(fn (Cart $cart) => ['id' => $cart->id, 'customer' => $cart->user, 'items' => $cart->items, 'value' => (int) $cart->items->sum(fn ($item) => $item->variant ? $item->variant->price * $item->quantity : 0), 'last_activity_at' => $cart->last_activity_at ?? $cart->updated_at, 'contacted_at' => $cart->contacted_at, 'contact_coupon' => $cart->contactCoupon]);
    }
}
