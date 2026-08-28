<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Coupon;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    /**
     * Creates the order and decreases stock as one indivisible operation.
     * $items contains product_variant_id and quantity from selected cart lines.
     */
    public function handle(Collection $items, array $attributes): Order
    {
        return DB::transaction(function () use ($items, $attributes) {
            $subtotal = 0;
            $lockedVariants = [];

            foreach ($items as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item['product_variant_id']);
                if ($item['quantity'] < 1 || $variant->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['stock' => "{$variant->sku} không còn đủ tồn kho."]);
                }
                $lockedVariants[] = [$variant, $item['quantity']];
                $subtotal += $variant->price * $item['quantity'];
            }

            [$coupon, $discount] = $this->lockAndValidateCoupon(
                $attributes['coupon_code'] ?? null,
                $subtotal
            );

            $order = Order::query()->create([
                'number' => (string) Str::uuid(),
                'user_id' => $attributes['user_id'] ?? null,
                'address_id' => $attributes['address_id'] ?? null,
                'coupon_id' => $coupon?->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $attributes['shipping_fee'] ?? 0,
                'total' => $subtotal - $discount + ($attributes['shipping_fee'] ?? 0),
                'payment_method' => $attributes['payment_method'],
                'payment_status' => $attributes['payment_status'] ?? 'pending',
                'status' => $attributes['status'] ?? 'pending',
            ]);

            $order->forceFill([
                'number' => 'ORD'.now()->format('Ymd').str_pad((string) $order->id, 8, '0', STR_PAD_LEFT),
            ])->save();

            foreach ($lockedVariants as [$variant, $quantity]) {
                $variant->decrement('stock', $quantity);
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'color' => $variant->color,
                    'size' => $variant->size,
                    'unit_price' => $variant->price,
                    'quantity' => $quantity,
                ]);
            }

            $coupon?->increment('used_count');

            return $order;
        });
    }

    private function lockAndValidateCoupon(?string $code, int $subtotal): array
    {
        if (blank($code)) {
            return [null, 0];
        }

        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($code)))
            ->lockForUpdate()
            ->first();

        $valid = $coupon
            && $coupon->is_active
            && (! $coupon->expires_at || $coupon->expires_at->isFuture())
            && $subtotal >= $coupon->minimum_order_value
            && ($coupon->usage_limit === null || $coupon->used_count < $coupon->usage_limit);

        if (! $valid) {
            throw ValidationException::withMessages(['coupon' => 'Mã giảm giá không hợp lệ hoặc đã hết lượt sử dụng.']);
        }

        $discount = $coupon->type === 'percent'
            ? (int) round($subtotal * $coupon->value / 100)
            : min($coupon->value, $subtotal);

        return [$coupon, $discount];
    }
}
