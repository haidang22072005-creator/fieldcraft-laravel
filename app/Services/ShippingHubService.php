<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class ShippingHubService
{
    public function summary(): array
    {
        $orders = Order::query()->whereNotNull('ghn_order_code')->where('status', '!=', 'cancelled')->with('user')->latest()->get();
        $buckets = ['waiting_pickup' => [], 'picked' => [], 'transporting' => [], 'delivering' => [], 'delivered' => [], 'failed_returning' => [], 'stuck' => []];
        $stuckAt = now()->subHours((int) config('services.admin.ops_stuck_hours', 48));
        foreach ($orders as $order) {
            $item = ['order_id' => $order->id, 'order_number' => $order->number, 'ghn_order_code' => $order->ghn_order_code, 'status' => $order->shipping_status, 'customer' => $order->user];
            $key = match ((string) $order->shipping_status) { 'pending', 'creating', 'created', 'order_created', 'ready_to_pick' => 'waiting_pickup', 'confirmed', 'picking' => 'picked', 'transporting' => 'transporting', 'delivering' => 'delivering', 'delivered' => 'delivered', 'failed', 'returning', 'returned' => 'failed_returning', default => 'waiting_pickup' };
            $buckets[$key][] = $item;
            if (in_array($order->status, ['shipping', 'pending', 'preparing'], true) && $order->updated_at?->lt($stuckAt)) $buckets['stuck'][] = array_merge($item, ['stuck_since' => $order->updated_at]);
        }
        return ['provider' => 'ghn', 'connected' => app(GHNService::class)->isConfigured(), 'counts' => collect($buckets)->map->count()->all(), 'shipments' => $buckets, 'providers' => app(ShippingProviderRegistry::class)->providers()];
    }
}
