<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    /**
     * Creates the order and decreases stock as one indivisible operation.
     * $items contains product_variant_id and quantity from selected cart lines.
     */
    public function handle(Collection $items, array $attributes): Order
    {
        return DB::transaction(function () use ($items, $attributes) {
            $subtotal = 0;
            $lockedVariants = [];

            foreach ($items as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item['product_variant_id']);
                if ($variant->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['stock' => "{$variant->sku} không còn đủ tồn kho."]);
                }
                $lockedVariants[] = [$variant, $item['quantity']];
                $subtotal += $variant->price * $item['quantity'];
            }

            $discount = $attributes['discount'] ?? 0;
            $order = Order::query()->create([
                ...$attributes,
                'number' => 'FC-'.now()->format('ymd').'-'.str_pad((string) (Order::query()->count() + 1), 4, '0', STR_PAD_LEFT),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $subtotal - $discount + ($attributes['shipping_fee'] ?? 0),
            ]);

            foreach ($lockedVariants as [$variant, $quantity]) {
                $variant->decrement('stock', $quantity);
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'color' => $variant->color,
                    'size' => $variant->size,
                    'unit_price' => $variant->price,
                    'quantity' => $quantity,
                ]);
            }

            return $order;
        });
    }
}
