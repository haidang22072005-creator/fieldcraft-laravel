<?php

namespace App\Services;

use App\Exceptions\GHNException;
use App\Models\Order;
use Illuminate\Support\Collection;

class GHNOrderService
{
    public function __construct(private GHNService $ghn) {}

    public function weightForItems(iterable $items): int
    {
        $total = 0;
        $fallback = max(1, (int) config('services.ghn.default_weight', 200));

        foreach ($items as $item) {
            $variant = is_array($item) ? ($item['variant'] ?? null) : $item->variant;
            $quantity = (int) (is_array($item) ? ($item['quantity'] ?? 1) : $item->quantity);
            $productWeight = $variant?->product?->getAttribute('weight');
            $weight = is_numeric($productWeight) && (int) $productWeight > 0
                ? (int) $productWeight
                : $fallback;
            $total += $weight * max(1, $quantity);
        }

        return max(1, $total);
    }

    public function buildPayload(Order $order): array
    {
        $toDistrictId = (int) $order->to_district_id;
        $toWardCode = (string) $order->to_ward_code;
        if ($toDistrictId < 1 || $toWardCode === '') {
            throw new GHNException('GHN destination is incomplete.');
        }

        $items = $order->items()->with('variant.product')->get();
        $payloadItems = $items->map(function ($item) {
            $weight = $item->variant?->product?->getAttribute('weight');
            $weight = is_numeric($weight) && (int) $weight > 0
                ? (int) $weight
                : max(1, (int) config('services.ghn.default_weight', 200));

            return [
                'name' => $item->product_name,
                'code' => $item->sku,
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->unit_price,
                'weight' => $weight,
            ];
        })->values()->all();

        return [
            'from_name' => config('services.ghn.from_name'),
            'from_phone' => config('services.ghn.from_phone'),
            'from_address' => config('services.ghn.from_address'),
            'from_province_name' => config('services.ghn.from_province_name'),
            'from_district_name' => config('services.ghn.from_district_name'),
            'from_ward_name' => config('services.ghn.from_ward_name'),
            'from_district_id' => (int) config('services.ghn.from_district_id'),
            'to_name' => $order->recipient_name,
            'to_phone' => $order->recipient_phone,
            'to_address' => $order->address_line,
            'to_ward_code' => $toWardCode,
            'to_district_id' => $toDistrictId,
            'service_type_id' => (int) config('services.ghn.service_type_id', 2),
            'payment_type_id' => 2,
            'required_note' => (string) config('services.ghn.required_note', 'KHONGCHOXEMHANG'),
            'weight' => $this->weightForItems($items),
            'cod_amount' => $order->payment_method === 'cod' ? (int) $order->total : 0,
            'content' => $items->pluck('product_name')->implode(', '),
            'items' => $payloadItems,
        ];
    }

    public function createOrder(Order $order): array
    {
        return $this->ghn->createOrder($this->buildPayload($order));
    }

    public function createAndStoreWaybill(Order $order): bool
    {
        if ($order->ghn_order_code || ! $order->to_district_id || ! $order->to_ward_code || ! $this->ghn->isConfigured()) {
            return false;
        }

        $waybill = $this->createOrder($order);
        $orderCode = $waybill['order_code'] ?? $waybill['orderCode'] ?? null;
        if (! $orderCode) {
            throw new GHNException('GHN did not return an order code.');
        }
        $order->forceFill([
            'ghn_order_code' => $orderCode,
            'ghn_total_fee' => (int) ($waybill['total_fee'] ?? $waybill['fee'] ?? $order->shipping_fee),
            'shipping_status' => 'created',
        ])->save();

        return true;
    }

    public function weightForCartItems(Collection $items): int
    {
        return $this->weightForItems($items);
    }
}
