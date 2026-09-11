<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrder;
use App\Actions\RetryMoMoPayment;
use App\Exceptions\GHNException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GHNOrderService;
use App\Services\MoMoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class MoMoPaymentController extends Controller
{
    public function retry(Request $request, Order $order, RetryMoMoPayment $retry, MoMoService $momo, CancelOrder $cancelOrder): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $payment = $retry->handle($order);

        try {
            $response = $momo->createPayment($order->fresh(), $payment);
            $payment->update([
                'pay_url' => $response['payUrl'],
                'provider_order_id' => $response['orderId'] ?? $order->number,
                'result_code' => $response['resultCode'] ?? 0,
                'message' => $response['message'] ?? null,
            ]);
            return redirect()->away($response['payUrl']);
        } catch (Throwable) {
            $payment->update(['status' => 'failed', 'message' => 'Không thể khởi tạo thanh toán MoMo.']);
            $cancelOrder->handle($order, 'failed');
            return back()->withErrors(['payment' => 'Không thể khởi tạo thanh toán MoMo. Vui lòng thử lại.']);
        }
    }

    public function showReturn(Request $request): View
    {
        $payment = Payment::query()->with('order')
            ->where('request_id', (string) $request->query('requestId'))
            ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id))
            ->first();

        return view('payments.momo-return', compact('payment'));
    }

    public function ipn(Request $request, MoMoService $momo, CancelOrder $cancelOrder, GHNOrderService $ghnOrders): JsonResponse
    {
        $payload = $request->all();
        if (! $momo->verifySignature($payload)) {
            return response()->json(['resultCode' => 1, 'message' => 'Invalid signature.'], 403);
        }

        $payment = Payment::query()->with('order')->where('request_id', (string) ($payload['requestId'] ?? ''))->first();
        if (! $payment || ! $payment->order || $payment->provider !== 'momo'
            || $payment->order->number !== (string) ($payload['orderId'] ?? '')
            || (string) ($payload['partnerCode'] ?? '') !== (string) config('services.momo.partner_code')) {
            return response()->json(['resultCode' => 2, 'message' => 'Payment not found.'], 404);
        }
        if ((int) ($payload['amount'] ?? -1) !== (int) $payment->amount || (int) $payment->amount !== (int) $payment->order->total) {
            return response()->json(['resultCode' => 3, 'message' => 'Amount mismatch.'], 422);
        }

        if ((int) ($payload['resultCode'] ?? -1) === 0) {
            [$order, $createWaybill] = DB::transaction(function () use ($payment, $payload) {
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $order = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);
                if (in_array($lockedPayment->status, ['failed', 'cancelled'], true) || $order->status === 'cancelled') {
                    return [$order, false];
                }
                if ($lockedPayment->status !== 'paid') {
                    $lockedPayment->update([
                        'status' => 'paid',
                        'transaction_id' => (string) ($payload['transId'] ?? ''),
                        'result_code' => 0,
                        'message' => $payload['message'] ?? null,
                        'paid_at' => now(),
                    ]);
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => $order->status === 'pending_payment' ? 'pending' : $order->status,
                    ]);
                }

                $createWaybill = ! $order->ghn_order_code
                    && $order->shipping_status === 'pending'
                    && $order->to_district_id && $order->to_ward_code;
                if ($createWaybill) $order->update(['shipping_status' => 'creating']);
                return [$order, $createWaybill];
            });

            if ($createWaybill) {
                try {
                    if (! $ghnOrders->createAndStoreWaybill($order->fresh())) {
                        Order::query()->whereKey($order->id)->whereNull('ghn_order_code')->update(['shipping_status' => 'pending']);
                    }
                } catch (GHNException) {
                    Order::query()->whereKey($order->id)->whereNull('ghn_order_code')->update(['shipping_status' => 'pending']);
                }
            }

            return response()->json(['resultCode' => 0, 'message' => 'Success']);
        }

        DB::transaction(function () use ($payment, $payload, $cancelOrder) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if (in_array($lockedPayment->status, ['paid', 'failed', 'cancelled'], true)) return;
            $status = (int) ($payload['resultCode'] ?? -1) === 1006 ? 'cancelled' : 'failed';
            $lockedPayment->update([
                'status' => $status,
                'transaction_id' => (string) ($payload['transId'] ?? ''),
                'result_code' => (int) ($payload['resultCode'] ?? -1),
                'message' => $payload['message'] ?? null,
            ]);
            $cancelOrder->handle($lockedPayment->order, $status);
        });

        return response()->json(['resultCode' => 0, 'message' => 'Acknowledged']);
    }
}
