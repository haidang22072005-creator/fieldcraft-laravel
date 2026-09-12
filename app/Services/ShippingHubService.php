<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class ShippingHubService
{
    public function summary(int $perPage = 20, int $page = 1): array
    {
        $statuses = ['waiting_pickup' => ['pending', 'creating', 'created', 'order_created', 'ready_to_pick'], 'picked' => ['confirmed', 'picking'], 'transporting' => ['transporting'], 'delivering' => ['delivering'], 'delivered' => ['delivered'], 'failed_returning' => ['failed', 'returning', 'returned']];
        $buckets = [];
        $case = "CASE WHEN shipping_status IN ('pending','creating','created','order_created','ready_to_pick') THEN 'waiting_pickup' WHEN shipping_status IN ('confirmed','picking') THEN 'picked' WHEN shipping_status = 'transporting' THEN 'transporting' WHEN shipping_status = 'delivering' THEN 'delivering' WHEN shipping_status = 'delivered' THEN 'delivered' WHEN shipping_status IN ('failed','returning','returned') THEN 'failed_returning' ELSE 'waiting_pickup' END";
        $counts = Order::query()->whereNotNull('ghn_order_code')->where('status', '!=', 'cancelled')->selectRaw($case.' AS bucket, COUNT(*) AS aggregate')->groupBy('bucket')->pluck('aggregate', 'bucket');
        $stuckAt = now()->subHours((int) config('services.admin.ops_stuck_hours', 48));
        foreach ($statuses as $key => $allowed) {
            $paginator = Order::query()->whereNotNull('ghn_order_code')->where('status', '!=', 'cancelled')->whereIn('shipping_status', $allowed)->with('user')->latest()->paginate(max(1, min(100, $perPage)), ['id', 'number', 'user_id', 'ghn_order_code', 'shipping_status', 'status', 'updated_at'], 'page_'.$key, $page);
            $buckets[$key] = ['data' => $paginator->through(fn (Order $order) => ['order_id' => $order->id, 'order_number' => $order->number, 'ghn_order_code' => $order->ghn_order_code, 'status' => $order->shipping_status, 'customer' => $order->user]), 'meta' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]];
        }
        $stuck = Order::query()->whereNotNull('ghn_order_code')->where('status', '!=', 'cancelled')->whereIn('status', ['shipping', 'pending', 'preparing'])->where('updated_at', '<', $stuckAt)->with('user')->latest()->paginate(max(1, min(100, $perPage)), ['id', 'number', 'user_id', 'ghn_order_code', 'shipping_status', 'status', 'updated_at'], 'page_stuck', $page);
        $buckets['stuck'] = ['data' => $stuck->through(fn (Order $order) => ['order_id' => $order->id, 'order_number' => $order->number, 'ghn_order_code' => $order->ghn_order_code, 'status' => $order->shipping_status, 'customer' => $order->user, 'stuck_since' => $order->updated_at]), 'meta' => ['current_page' => $stuck->currentPage(), 'per_page' => $stuck->perPage(), 'total' => $stuck->total(), 'last_page' => $stuck->lastPage()]];
        return ['provider' => 'ghn', 'connected' => app(GHNService::class)->isConfigured(), 'counts' => collect(array_keys($statuses))->mapWithKeys(fn ($key) => [$key => (int) ($counts->get($key, 0))])->merge(['stuck' => $stuck->total()])->all(), 'shipments' => $buckets, 'providers' => app(ShippingProviderRegistry::class)->providers()];
    }
}
