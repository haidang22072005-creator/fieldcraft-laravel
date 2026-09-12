<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundWorkflowService
{
    public function markProcessing(Order $order, int $actorId): Payment
    {
        return DB::transaction(function () use ($order, $actorId): Payment {
            $payment = $this->latestRefundablePayment($order);
            if ($payment->refund_status === 'pending') return $payment;
            if ($payment->refund_status !== 'required') $this->invalidState();
            $payment->update(['refund_status' => 'pending']);
            app(ActivityLogService::class)->recordOnce('payment.refund_processing', $payment, ['refund_status' => 'pending'], $actorId);
            app(AdminNotificationService::class)->notifyOnce('payment_refund_processing', 'Đang xử lý hoàn tiền', $order->number, ['refund_status' => 'pending'], $order);
            return $payment->fresh('order');
        });
    }

    public function confirmRefund(Order $order, string $reference, int $actorId): Payment
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw ValidationException::withMessages(['refund_reference' => 'Cần mã xác nhận hoàn tiền từ nhà cung cấp.']);
        }

        return DB::transaction(function () use ($order, $reference, $actorId): Payment {
            $payment = Payment::query()->where('order_id', $order->id)->latest('id')->lockForUpdate()->first();
            if (! $payment || $payment->status !== 'paid') $this->invalidState();
            if ($payment->refund_status === 'refunded') {
                if ($payment->refund_reference !== $reference) {
                    throw ValidationException::withMessages(['refund_reference' => 'Giao dịch đã được xác nhận hoàn tiền với mã khác.']);
                }
                return $payment->fresh('order');
            }
            if (! in_array($payment->refund_status, ['required', 'pending'], true)) $this->invalidState();
            $payment->update(['refund_status' => 'refunded', 'refund_reference' => $reference, 'refunded_at' => now(), 'refund_confirmed_by' => $actorId]);
            return $payment->fresh('order');
        });
    }

    public function markFailed(Order $order, string $reason, int $actorId): Payment
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['refund_reason' => 'Cần nêu lý do hoàn tiền thất bại.']);
        }

        return DB::transaction(function () use ($order, $reason, $actorId): Payment {
            $payment = $this->latestRefundablePayment($order);
            if ($payment->refund_status === 'failed') return $payment;
            if (! in_array($payment->refund_status, ['required', 'pending'], true)) $this->invalidState();
            $payment->update(['refund_status' => 'failed', 'refund_reason' => $reason]);
            app(ActivityLogService::class)->recordOnce('payment.refund_failed', $payment, ['refund_status' => 'failed', 'reason' => $reason], $actorId);
            app(AdminNotificationService::class)->notifyOnce('payment_refund_failed', 'Hoàn tiền thất bại cần xử lý lại', $order->number, ['refund_status' => 'failed'], $order);
            return $payment->fresh('order');
        });
    }

    private function latestRefundablePayment(Order $order): Payment
    {
        $payment = Payment::query()->where('order_id', $order->id)->latest('id')->lockForUpdate()->first();
        if (! $payment || $payment->status !== 'paid') $this->invalidState();
        if (! in_array($payment->refund_status, ['required', 'pending'], true)) $this->invalidState();
        return $payment;
    }

    private function invalidState(): never
    {
        throw ValidationException::withMessages(['refund' => 'Thanh toán hiện không ở trạng thái có thể xử lý hoàn tiền.']);
    }
}
