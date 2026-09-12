<?php

namespace App\Actions;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Exceptions\GHNException;
use App\Services\GHNOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    public function __construct(private GHNOrderService $ghnOrders) {}

    public function handle(Order $order, string $paymentStatus = 'cancelled'): ?string
    {
        return DB::transaction(function () use ($order, $paymentStatus) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status === 'cancelled') {
                return null;
            }
            if ($this->isProviderPaidOnline($lockedOrder)) {
                throw ValidationException::withMessages(['order' => 'Đơn đã thanh toán trực tuyến. Vui lòng yêu cầu hoàn tiền.']);
            }
            if (! in_array($lockedOrder->status, ['pending', 'pending_payment', 'preparing', 'confirmed', 'packing', 'shipping'], true)) {
                throw ValidationException::withMessages(['order' => 'Đơn hàng không còn trong trạng thái có thể hủy.']);
            }

            if ($lockedOrder->ghn_order_code) {
                try {
                    // Keep all local side effects after remote GHN confirmation.
                    $this->ghnOrders->cancelWaybill($lockedOrder);
                } catch (GHNException $exception) {
                    Log::warning('Local cancellation blocked because GHN did not confirm cancellation.', [
                        'order_id' => $lockedOrder->id,
                        'ghn_order_code' => $lockedOrder->ghn_order_code,
                        'reason' => $exception->getMessage(),
                    ]);
                    $message = $exception->getMessage() === 'GHN order is not cancellable.'
                        ? 'Không thể hủy đơn vì vận đơn đã bước vào quá trình vận chuyển.'
                        : 'Không thể hủy đơn vì GHN chưa xác nhận hủy vận đơn. Vui lòng thử lại.';
                    throw ValidationException::withMessages(['order' => $message]);
                }
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

    private function isProviderPaidOnline(Order $order): bool
    {
        return $order->payment_status === 'paid'
            && in_array((string) $order->payment_method, ['momo', 'payos', 'bank_qr', 'online'], true);
    }
}
