<?php

namespace App\Services;

use App\Models\BootPassport;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BootPassportService
{
    public function generateForUser(int $userId): array
    {
        $items = OrderItem::query()->whereHas('order', fn ($q) => $q->where('user_id', $userId)->where('status', 'completed'))->with(['order', 'variant.product', 'review', 'bootPassport'])->get();
        return $items->filter(fn (OrderItem $item) => $this->eligible($item))->map(fn (OrderItem $item) => $this->create($item))->values()->all();
    }

    public function generateAll(): int
    {
        $count = 0;
        User::query()->where('role', 'customer')->pluck('id')->each(function (int $userId) use (&$count): void { $count += count($this->generateForUser($userId)); });
        return $count;
    }

    public function generateForItem(OrderItem $item): ?BootPassport
    {
        return $this->eligible($item->loadMissing(['order', 'variant.product', 'review'])) ? $this->create($item) : null;
    }

    private function eligible(OrderItem $item): bool
    {
        return $item->order?->status === 'completed' && $item->variant?->product && in_array(strtolower((string) $item->variant->product->category), ['giày', 'football', 'boots', 'shoes'], true);
    }

    private function create(OrderItem $item): BootPassport
    {
        return DB::transaction(function () use ($item): BootPassport {
            $existing = BootPassport::query()->where('order_item_id', $item->id)->first();
            if ($existing) return $existing->load(['order', 'orderItem', 'variant']);
            $variant = $item->variant;
            return BootPassport::create(['passport_code' => 'PASS-'.strtoupper(Str::random(12)), 'user_id' => $item->order->user_id, 'order_id' => $item->order_id, 'order_item_id' => $item->id, 'product_variant_id' => $item->product_variant_id, 'variant_snapshot' => ['product_name' => $item->product_name, 'sku' => $item->sku, 'unit_price' => $item->unit_price, 'quantity' => $item->quantity, 'variant_id' => $item->product_variant_id], 'color' => $item->color, 'size' => $item->size, 'stud_type' => $variant?->stud_type, 'purchase_date' => $item->order->completed_at?->toDateString() ?? $item->order->created_at->toDateString(), 'review_status' => $item->review ? $item->review->status : 'not_reviewed', 'second_hand_eligible' => true])->load(['order', 'orderItem', 'variant']);
        });
    }
}
