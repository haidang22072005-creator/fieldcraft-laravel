<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\ActivityLogService;
use App\Services\AdminNotificationService;
use App\Services\LoyaltyService;

class PaymentObserver
{
    public function updated(Payment $payment): void
    {
        if (! $payment->wasChanged('refund_status') || $payment->refund_status !== 'refunded' || ! $payment->refunded_at) {
            return;
        }

        $payment->loadMissing('order');
        if (! $payment->order) {
            return;
        }

        app(LoyaltyService::class)->recordConfirmedRefund($payment);
        app(AdminNotificationService::class)->notifyOnce('payment_refunded', 'Đã xác nhận hoàn tiền', $payment->order->number, ['refund_status' => 'refunded'], $payment->order);
        app(ActivityLogService::class)->recordOnce('payment.refund_confirmed', $payment, ['refund_status' => 'refunded', 'refund_reference' => $payment->refund_reference], $payment->refund_confirmed_by);
    }
}
