<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrder;
use App\Actions\RetryMoMoPayment;
use App\Exceptions\GHNException;
use App\Exceptions\MoMoInitializationException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GHNOrderService;
use App\Services\MoMoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MoMoPaymentController extends Controller
{
    public function simulateSuccess(Request $request, Order $order, MoMoService $momo, CancelOrder $cancelOrder, GHNOrderService $ghnOrders): RedirectResponse
    {
        abort_unless(app()->environment('local') && config('services.momo.simulator_enabled') === true, 404);
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($order->payment_method !== 'momo' || $order->payment_status === 'paid') {
            throw ValidationException::withMessages(['payment' => 'Đơn hàng không thể giả lập thanh toán.']);
        }

        $payment = $order->payments()->where('provider', 'momo')->latest('id')->first();
        if (! $payment || $payment->status !== 'pending') {
            throw ValidationException::withMessages(['payment' => 'Không có giao dịch MoMo đang chờ xử lý.']);
        }

        $payload = $momo->signResultPayload([
            'partnerCode' => (string) config('services.momo.partner_code'),
            'orderId' => $payment->provider_order_id,
            'requestId' => $payment->request_id,
            'amount' => (string) $payment->amount,
            'orderInfo' => 'Thanh toán đơn hàng '.$order->number,
            'orderType' => 'momo_wallet',
            'transId' => 'LAB-'.Str::upper((string) Str::ulid()),
            'resultCode' => 0,
            'message' => 'Successful.',
            'payType' => 'credit',
            'responseTime' => (string) now()->getTimestampMs(),
            'extraData' => '',
        ]);

        if (! $momo->verifySignature($payload) || ! $this->identityMatches($payment->load('order'), $payload) || ! $this->amountMatches($payment, $payload)) {
            throw ValidationException::withMessages(['payment' => 'Dữ liệu giả lập không hợp lệ.']);
        }

        $this->processResult($payment, $payload, $cancelOrder, $ghnOrders);
        return redirect()->route('purchases')->with('success', 'LAB: Đã giả lập thanh toán MoMo thành công.');
    }

    public function retry(Request $request, Order $order, RetryMoMoPayment $retry, MoMoService $momo, CancelOrder $cancelOrder): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $payment = $retry->handle($order);

        try {
            $response = $momo->createPayment($order->fresh(), $payment);
            $payment->update([
                'pay_url' => $response['payUrl'],
                'result_code' => $response['resultCode'] ?? 0,
                'message' => $response['message'] ?? null,
            ]);
            return redirect()->away($response['payUrl']);
        } catch (MoMoInitializationException $exception) {
            $payment->update(['status' => 'failed', 'result_code' => $exception->resultCode, 'message' => $exception->providerMessage]);
            $cancelOrder->handle($order, 'failed');
            return back()->withErrors(['payment' => 'Không thể khởi tạo thanh toán MoMo. Vui lòng thử lại.']);
        } catch (Throwable) {
            $payment->update(['status' => 'failed', 'message' => 'Không thể khởi tạo thanh toán MoMo.']);
            $cancelOrder->handle($order, 'failed');
            return back()->withErrors(['payment' => 'Không thể khởi tạo thanh toán MoMo. Vui lòng thử lại.']);
        }
    }

    public function showReturn(Request $request, MoMoService $momo, CancelOrder $cancelOrder, GHNOrderService $ghnOrders): View
    {
        $payload = $request->query();
        $payment = Payment::query()->with('order')
            ->where('request_id', $this->scalar($request->query('requestId')))
            ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id))
            ->first();

        if ($payment
            && $momo->verifySignature($payload)
            && $this->identityMatches($payment, $payload)
            && $this->amountMatches($payment, $payload)
            && ($payment->status === 'pending' || (int) ($payload['resultCode'] ?? -1) === 0)) {
            $this->processResult($payment, $payload, $cancelOrder, $ghnOrders);
            $payment = $payment->fresh('order');
        }

        return view('payments.momo-return', compact('payment'));
    }

    public function ipn(Request $request, MoMoService $momo, CancelOrder $cancelOrder, GHNOrderService $ghnOrders): JsonResponse
    {
        $payload = $request->all();
        Log::info('MoMo IPN received.', [
            'request_id' => Str::limit($this->scalar($request->input('requestId')), 64, ''),
            'order_id' => Str::limit($this->scalar($request->input('orderId')), 64, ''),
            'result_code' => $this->scalar($request->input('resultCode')),
            'trans_id_present' => $this->scalar($request->input('transId')) !== '',
        ]);
        if (! $momo->verifySignature($payload)) {
            return response()->json(['resultCode' => 1, 'message' => 'Invalid signature.'], 403);
        }

        $payment = Payment::query()->with('order')->where('request_id', $this->scalar($payload['requestId'] ?? null))->first();
        if (! $payment || ! $this->identityMatches($payment, $payload)) {
            return response()->json(['resultCode' => 2, 'message' => 'Payment not found.'], 404);
        }
        if (! $this->amountMatches($payment, $payload)) {
            return response()->json(['resultCode' => 3, 'message' => 'Amount mismatch.'], 422);
        }

        $this->processResult($payment, $payload, $cancelOrder, $ghnOrders);
        return response()->json(['resultCode' => 0, 'message' => 'Acknowledged']);
    }

    protected function processResult(Payment $payment, array $payload, CancelOrder $cancelOrder, GHNOrderService $ghnOrders): void
    {
        if ((int) ($payload['resultCode'] ?? -1) === 0) {
            [$order, $createWaybill] = DB::transaction(function () use ($payment, $payload) {
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $order = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);
                if ($lockedPayment->status !== 'paid') {
                    $lockedPayment->update([
                        'status' => 'paid',
                        'transaction_id' => (string) ($payload['transId'] ?? ''),
                        'result_code' => 0,
                        'message' => $payload['message'] ?? null,
                        'paid_at' => now(),
                    ]);
                }

                if ($order->status === 'cancelled') {
                    $lockedPayment->update([
                        'refund_status' => $lockedPayment->refund_status === 'refunded' ? 'refunded' : 'required',
                        'refund_reason' => $lockedPayment->refund_reason ?: 'Thanh toán được ghi nhận sau khi đơn đã hủy.',
                    ]);
                    $order->update(['payment_status' => 'paid']);
                    app(\App\Services\AdminNotificationService::class)->notifyOnce('payment_refund_required', 'Thanh toán thành công cần hoàn tiền', $order->number, ['refund_status' => 'required'], $order);
                    app(\App\Services\ActivityLogService::class)->recordOnce('payment.success_refund_required', $order);

                    return [$order, false];
                }

                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'pending_payment' ? 'pending' : $order->status,
                ]);

                $createWaybill = ! $order->ghn_order_code
                    && $order->shipping_status === 'pending'
                    && $order->to_district_id && $order->to_ward_code;
                if ($createWaybill) $order->update(['shipping_status' => 'creating']);
                app(\App\Services\AdminNotificationService::class)->notifyOnce('payment_success', 'Thanh toán thành công', $order->number, [], $order);
                app(\App\Services\ActivityLogService::class)->recordOnce('payment.success', $order);
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

            return;
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

    }

    protected function identityMatches(Payment $payment, array $payload): bool
    {
        return $payment->order
            && $payment->provider === 'momo'
            && $payment->request_id === $this->scalar($payload['requestId'] ?? null)
            && $payment->provider_order_id === $this->scalar($payload['orderId'] ?? null)
            && $this->scalar($payload['partnerCode'] ?? null) === (string) config('services.momo.partner_code');
    }

    protected function amountMatches(Payment $payment, array $payload): bool
    {
        return is_numeric($payload['amount'] ?? null)
            && (int) $payload['amount'] === (int) $payment->amount
            && (int) $payment->amount === (int) $payment->order->total;
    }

    protected function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
