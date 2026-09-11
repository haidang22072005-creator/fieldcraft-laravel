<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GHNOrderService;
use App\Services\MoMoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * LOCAL-ONLY sandbox card payment page.
 * Extends MoMoPaymentController to reuse the verified processResult pipeline.
 * Only accessible when app is in 'local'/'testing' environment AND simulator_enabled=true.
 * NEVER sets DB payment_status=paid directly — always goes through processResult().
 */
class MoMoSandboxController extends MoMoPaymentController
{
    /** Cards: last-4 → [resultCode, message] */
    private const CARDS = [
        '0018' => [0,    'Thành công'],
        '0026' => [1001, 'Thẻ bị khóa'],
        '0034' => [1001, 'Không đủ số dư'],
        '0042' => [1001, 'Vượt hạn mức'],
    ];

    private function gate(): void
    {
        abort_unless(
            (app()->environment('local') || app()->environment('testing'))
            && config('services.momo.simulator_enabled') === true,
            404
        );
    }

    public function show(Request $request, Order $order): View
    {
        $this->gate();
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->payment_method !== 'momo' || $order->payment_status === 'paid') {
            abort(403, 'Đơn hàng không thể truy cập sandbox.');
        }

        $payment = $order->payments()->where('provider', 'momo')->latest('id')->first();
        abort_if(! $payment || $payment->status !== 'pending', 403, 'Không có giao dịch MoMo đang chờ xử lý.');

        return view('payments.momo-sandbox', compact('order', 'payment'));
    }

    public function submit(
        Request $request,
        Order $order,
        MoMoService $momo,
        CancelOrder $cancelOrder,
        GHNOrderService $ghnOrders
    ): RedirectResponse {
        $this->gate();
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->payment_method !== 'momo' || $order->payment_status === 'paid') {
            abort(403, 'Đơn hàng không thể giả lập thanh toán.');
        }

        $payment = $order->payments()->where('provider', 'momo')->latest('id')->first();
        if (! $payment || $payment->status !== 'pending') {
            abort(403, 'Không có giao dịch MoMo đang chờ xử lý.');
        }

        // Determine result from test card last-4; NEVER persist CVC or log full card
        $cardNumber = preg_replace('/\s+/', '', (string) $request->input('card_number', ''));
        $last4      = substr($cardNumber, -4);
        [$resultCode, $message] = self::CARDS[$last4] ?? [1001, 'Thẻ thử nghiệm không hợp lệ'];

        // Build a MoMo-like signed result payload (same as simulateSuccess)
        $payload = $momo->signResultPayload([
            'partnerCode'  => (string) config('services.momo.partner_code'),
            'orderId'      => $payment->provider_order_id,
            'requestId'    => $payment->request_id,
            'amount'       => (string) $payment->amount,
            'orderInfo'    => 'Thanh toán đơn hàng ' . $order->number,
            'orderType'    => 'momo_wallet',
            'transId'      => 'LAB-' . Str::upper((string) Str::ulid()),
            'resultCode'   => $resultCode,
            'message'      => $message,
            'payType'      => 'credit',
            'responseTime' => (string) now()->getTimestampMs(),
            'extraData'    => '',
        ]);

        // Verify with same HMAC + identity + amount checks used in production
        if (! $momo->verifySignature($payload)
            || ! $this->identityMatches($payment->load('order'), $payload)
            || ! $this->amountMatches($payment, $payload)) {
            return back()->withErrors(['payment' => 'Dữ liệu giả lập không hợp lệ.']);
        }

        // Run through the same processResult pipeline — NEVER sets paid directly
        $this->processResult($payment, $payload, $cancelOrder, $ghnOrders);

        if ($resultCode === 0) {
            return redirect()->route('purchases')->with('success', 'Thanh toán sandbox thành công.');
        }

        return redirect()->route('purchases');
    }
}
