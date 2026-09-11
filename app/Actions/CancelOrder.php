<?php

namespace App\Actions;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function handle(Order $order, string $paymentStatus = 'cancelled'): ?string
    {
        return DB::transaction(function () use ($order, $paymentStatus) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status === 'cancelled') {
                return null;
            }

            foreach ($lockedOrder->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::query()->lockForUpdate()->find($item->product_variant_id)?->increment('stock', (int) $item->quantity);
                }
            }

            $usage = $lockedOrder->couponUsage()->lockForUpdate()->first();
            if ($usage) {
                $coupon = Coupon::query()->lockForUpdate()->find($usage->coupon_id);
                $usage->delete();
                if ($coupon && $coupon->used_count > 0) $coupon->decrement('used_count');
            }

            $ghnOrderCode = $lockedOrder->ghn_order_code;
            $lockedOrder->update([
                'status' => 'cancelled',
                'payment_status' => $paymentStatus,
                'shipping_status' => 'cancelled',
            ]);

            return $ghnOrderCode;
        });
    }
}
