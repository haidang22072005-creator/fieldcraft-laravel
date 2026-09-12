<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\LoyaltyPointTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function customerMetrics(): Builder
    {
        return User::query()->where('role', 'customer')
            ->withCount(['orders as completed_order_count' => fn ($query) => $this->eligibleCompletedOrders($query)])
            ->withSum(['orders as completed_spend' => fn ($query) => $this->eligibleCompletedOrders($query)], 'total')
            ->withMax(['orders as last_completed_purchase' => fn ($query) => $this->eligibleCompletedOrders($query)], 'created_at')
            ->withSum('loyaltyPointTransactions as loyalty_points', 'points');
    }

    public function eligibleCompletedOrders(Builder $query): Builder
    {
        return $query->where('status', 'completed')->whereDoesntHave('payments', fn ($payment) => $payment->where('refund_status', 'refunded'));
    }

    public function profile(User $user): array
    {
        $metrics = $this->customerMetrics()->whereKey($user->id)->first();
        return $this->profileFromMetrics((array) $metrics->getAttributes());
    }

    public function profileFromMetrics(array $metrics): array
    {
        $spend = (int) ($metrics['completed_spend'] ?? 0);
        $thresholds = collect(config('services.loyalty.thresholds', []))->mapWithKeys(fn ($value, $tier) => [strtoupper((string) $tier) => (int) $value])->sort()->all();
        $current = 'ROOKIE';
        $currentThreshold = 0;
        $nextTier = null;
        $nextThreshold = null;
        foreach ($thresholds as $tier => $threshold) {
            if ($spend >= $threshold) { $current = $tier; $currentThreshold = $threshold; }
            elseif ($nextTier === null) { $nextTier = $tier; $nextThreshold = $threshold; }
        }
        $progress = $nextThreshold === null ? 100 : (int) min(100, max(0, round(($spend - $currentThreshold) / max(1, $nextThreshold - $currentThreshold) * 100)));
        return ['tier' => $current, 'completed_order_count' => (int) ($metrics['completed_order_count'] ?? 0), 'last_completed_purchase' => $metrics['last_completed_purchase'] ?? null, 'completed_spend' => $spend, 'loyalty_points' => (int) ($metrics['loyalty_points'] ?? 0), 'current_threshold' => $currentThreshold, 'next_tier' => $nextTier, 'next_threshold' => $nextThreshold, 'amount_to_next_tier' => $nextThreshold === null ? 0 : max(0, $nextThreshold - $spend), 'progress_percent' => $progress];
    }

    public function recordCompletedOrder(Order $order): ?\App\Models\LoyaltyPointTransaction
    {
        if ($order->status !== 'completed' || ! $order->user_id) return null;
        $points = intdiv((int) $order->total, (int) config('services.loyalty.points_per_currency', 10000));
        if ($points <= 0) return null;
        return DB::transaction(function () use ($order, $points): LoyaltyPointTransaction {
            $transaction = LoyaltyPointTransaction::query()->firstOrCreate(['event_key' => 'order:'.$order->id.':completed'], ['user_id' => $order->user_id, 'order_id' => $order->id, 'type' => 'earn', 'points' => $points, 'reason' => 'Đơn hàng hoàn thành']);
            if ($transaction->wasRecentlyCreated) {
                app(ActivityLogService::class)->recordOnce('loyalty.earned', $transaction, ['points' => $points, 'order_id' => $order->id]);
            }
            return $transaction;
        });
    }

    public function recordConfirmedRefund(Payment $payment): ?LoyaltyPointTransaction
    {
        if ($payment->refund_status !== 'refunded' || ! $payment->refunded_at || $payment->status !== 'paid') {
            return null;
        }

        $payment->loadMissing('order');
        return $payment->order ? $this->recordClawback($payment->order, 'Đơn hàng đã được xác nhận hoàn tiền', true) : null;
    }

    public function recordClawback(Order $order, string $reason = 'Đơn hàng bị hoàn hoặc hủy', bool $confirmedRefund = false): ?LoyaltyPointTransaction
    {
        if (! $confirmedRefund) {
            $confirmedRefund = $order->payments()->where('status', 'paid')->where('refund_status', 'refunded')->whereNotNull('refunded_at')->exists();
        }
        if (! $confirmedRefund) return null;

        return DB::transaction(function () use ($order, $reason): ?LoyaltyPointTransaction {
            $earned = LoyaltyPointTransaction::query()->where('event_key', 'order:'.$order->id.':completed')->lockForUpdate()->first();
            if (! $earned || $earned->points <= 0) return null;
            $transaction = LoyaltyPointTransaction::query()->firstOrCreate(['event_key' => 'order:'.$order->id.':clawback'], ['user_id' => $order->user_id, 'order_id' => $order->id, 'type' => 'clawback', 'points' => -abs((int) $earned->points), 'reason' => $reason]);
            if ($transaction->wasRecentlyCreated) {
                app(ActivityLogService::class)->recordOnce('loyalty.clawback', $transaction, ['points' => $transaction->points, 'order_id' => $order->id]);
            }
            return $transaction;
        });
    }
}
