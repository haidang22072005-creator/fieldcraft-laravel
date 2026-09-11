<?php

namespace App\Actions;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RetryMoMoPayment
{
    public function handle(Order $order): Payment
    {
        return $this->handleForProvider($order, 'momo');
    }

    public function handleForProvider(Order $order, string $provider): Payment
    {
        if (! in_array($provider, ['momo', 'bank_qr'], true)) throw new \InvalidArgumentException('Unsupported payment provider.');
        $lastPaymentId = $order->payments()->latest('id')->value('id');

        return DB::transaction(function () use ($order, $lastPaymentId, $provider) {
            $lastPayment = $lastPaymentId ? Payment::query()->lockForUpdate()->find($lastPaymentId) : null;
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_status === 'paid' || $order->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['payment' => 'Đơn hàng đã được thanh toán.']);
            }
            if (! $lastPayment || ! in_array($lastPayment->status, ['failed', 'cancelled'], true)
                || $order->payments()->latest('id')->value('id') !== $lastPayment->id
                || $order->status !== 'cancelled' || $order->ghn_order_code) {
                throw ValidationException::withMessages(['payment' => 'Đơn hàng chưa thể thanh toán lại.']);
            }

            foreach ($order->items as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->find($item->product_variant_id);
                if (! $variant || $variant->stock < $item->quantity) {
                    throw ValidationException::withMessages(['payment' => 'Sản phẩm không còn đủ tồn kho để thanh toán lại.']);
                }
                $variant->decrement('stock', (int) $item->quantity);
            }

            if ($order->coupon_id) {
                $coupon = Coupon::query()->lockForUpdate()->find($order->coupon_id);
                $valid = $coupon && $coupon->is_active
                    && (! $coupon->expires_at || $coupon->expires_at->isFuture())
                    && $order->subtotal >= $coupon->minimum_order_value
                    && ($coupon->usage_limit === null || $coupon->used_count < $coupon->usage_limit)
                    && ($coupon->per_user_limit === null || $coupon->usages()->where('user_id', $order->user_id)->count() < $coupon->per_user_limit);
                $discount = $coupon?->type === 'percent'
                    ? (int) round($order->subtotal * $coupon->value / 100)
                    : min((int) ($coupon?->value ?? 0), (int) $order->subtotal);
                if (! $valid || $discount !== (int) $order->discount) {
                    throw ValidationException::withMessages(['payment' => 'Mã giảm giá không còn hợp lệ để thanh toán lại.']);
                }
                CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $order->user_id, 'order_id' => $order->id, 'used_at' => now()]);
                $coupon->increment('used_count');
            }

            $order->update(['payment_method' => $provider, 'payment_status' => 'pending', 'status' => 'pending_payment', 'shipping_status' => 'pending']);

            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => $provider,
                'request_id' => (string) Str::uuid(),
                'amount' => $order->total,
                'status' => 'pending',
            ]);
            $providerOrderId = $provider === 'bank_qr'
                ? now()->format('ymdHis').str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT)
                : $order->number.'-P'.$payment->id;
            $payment->update(['provider_order_id' => $providerOrderId]);
            return $payment;
        });
    }
}
