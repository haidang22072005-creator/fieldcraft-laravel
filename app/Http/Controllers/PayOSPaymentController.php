<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrder;
use App\Actions\RetryMoMoPayment;
use App\Exceptions\GHNException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GHNOrderService;
use App\Services\PayOSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PayOSPaymentController extends Controller
{
    public function webhook(Request $request, PayOSService $payOS, CancelOrder $cancelOrder, GHNOrderService $ghnOrders): JsonResponse
    {
        $payload = $request->all();
        if (! $payOS->verifyWebhook($payload)) return response()->json(['code' => '01', 'desc' => 'Invalid signature'], 403);
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $orderCode = is_numeric($data['orderCode'] ?? null) ? (string) $data['orderCode'] : '';
        $payment = Payment::query()->with('order')->where('provider', 'bank_qr')->where('provider_order_id', $orderCode)->first();
        if (! $payment || ! $payment->order) return response()->json(['code' => '02', 'desc' => 'Payment not found'], 404);
        if (! is_numeric($data['amount'] ?? null) || (int) $data['amount'] !== (int) $payment->amount || (int) $payment->amount !== (int) $payment->order->total) {
            return response()->json(['code' => '03', 'desc' => 'Amount mismatch'], 422);
        }

        if (($payload['success'] ?? false) === true && ($data['code'] ?? null) === '00') {
            [$order, $createWaybill] = DB::transaction(function () use ($payment, $data) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
                if ($payment->status !== 'paid') {
                    $payment->update(['status' => 'paid', 'transaction_id' => (string) ($data['reference'] ?? $data['paymentLinkId'] ?? ''), 'result_code' => 0, 'message' => $data['desc'] ?? 'Thành công', 'paid_at' => now()]);
                }
                if ($order->status === 'cancelled') {
                    $payment->update([
                        'refund_status' => $payment->refund_status === 'refunded' ? 'refunded' : 'required',
                        'refund_reason' => $payment->refund_reason ?: 'Thanh toán được ghi nhận sau khi đơn đã hủy.',
                    ]);
                    $order->update(['payment_status' => 'paid']);

                    return [$order, false];
                }
                $order->update(['payment_status' => 'paid', 'status' => $order->status === 'pending_payment' ? 'pending' : $order->status]);
                $createWaybill = ! $order->ghn_order_code && $order->shipping_status === 'pending' && $order->to_district_id && $order->to_ward_code;
                if ($createWaybill) $order->update(['shipping_status' => 'creating']);
                return [$order, $createWaybill];
            });

            if ($createWaybill) {
                try {
                    if (! $ghnOrders->createAndStoreWaybill($order->fresh())) Order::whereKey($order->id)->whereNull('ghn_order_code')->update(['shipping_status' => 'pending']);
                } catch (GHNException) {
                    Order::whereKey($order->id)->whereNull('ghn_order_code')->update(['shipping_status' => 'pending']);
                }
            }
        } else {
            DB::transaction(function () use ($payment, $data, $cancelOrder) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                if (in_array($payment->status, ['paid', 'failed', 'cancelled'], true)) return;
                $payment->update(['status' => 'failed', 'result_code' => is_numeric($data['code'] ?? null) ? (int) $data['code'] : null, 'message' => $data['desc'] ?? 'Thanh toán thất bại']);
                $cancelOrder->handle($payment->order, 'failed');
            });
        }

        return response()->json(['code' => '00', 'desc' => 'success']);
    }

    public function retry(Request $request, Order $order, RetryMoMoPayment $retry, PayOSService $payOS, CancelOrder $cancelOrder): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id && $order->payment_method === 'bank_qr', 403);
        $payment = $retry->handleForProvider($order, 'bank_qr');
        try {
            return $this->initialize($order->fresh(), $payment, $payOS);
        } catch (Throwable) {
            $payment->update(['status' => 'failed', 'message' => 'Không thể khởi tạo thanh toán payOS.']);
            $cancelOrder->handle($order, 'failed');
            return back()->withErrors(['payment' => 'Không thể khởi tạo thanh toán payOS. Vui lòng thử lại.']);
        }
    }

    public function showReturn(Request $request): RedirectResponse
    {
        $payment = $this->ownedPaymentFromOrderCode($request);
        return redirect()->route('purchases')->with('success', $payment?->status === 'paid' ? 'Thanh toán chuyển khoản thành công.' : 'Đang chờ payOS xác nhận thanh toán.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->ownedPaymentFromOrderCode($request);
        return redirect()->route('purchases')->with('success', 'Bạn đã quay lại từ trang thanh toán payOS.');
    }

    public function status(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->provider === 'bank_qr' && $payment->order?->user_id === $request->user()->id, 403);
        return response()->json(['status' => $payment->status, 'order_number' => $payment->order->number, 'amount' => $payment->amount]);
    }

    private function initialize(Order $order, Payment $payment, PayOSService $payOS): RedirectResponse
    {
        $response = $payOS->createPayment($order, $payment);
        $payment->update(['pay_url' => $response['checkoutUrl'], 'transaction_id' => $response['paymentLinkId'] ?? null, 'result_code' => 0, 'message' => $response['status'] ?? 'PENDING']);
        return redirect()->away($response['checkoutUrl']);
    }

    private function ownedPaymentFromOrderCode(Request $request): ?Payment
    {
        $orderCode = $request->query('orderCode');
        if (! is_numeric($orderCode)) return null;
        return Payment::query()->where('provider', 'bank_qr')->where('provider_order_id', (string) $orderCode)
            ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id))->first();
    }
}
