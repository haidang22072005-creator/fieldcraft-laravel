<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminNotificationService
{
    public function notify(string $type, string $title, ?string $body = null, array $data = [], ?Model $subject = null): void
    {
        $payload = array_merge($data, $subject ? ['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()] : []);
        User::query()->whereIn('role', ['admin', 'super-admin'])->pluck('id')->each(fn (int $id) => AdminNotification::create(['user_id' => $id, 'type' => $type, 'title' => $title, 'body' => $body, 'data' => $payload]));
    }

    public function notifyOnce(string $type, string $title, ?string $body = null, array $data = [], ?Model $subject = null): void
    {
        if (AdminNotification::query()->where('type', $type)->where('body', $body)->exists()) return;
        $this->notify($type, $title, $body, $data, $subject);
    }

    public function syncImportantSizeLowStock(): void
    {
        ProductVariant::query()->whereIn('size', (array) config('services.admin.important_sizes', ['40', '41']))->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [(int) config('services.admin.low_stock_threshold', 5)])->chunkById(200, fn ($variants) => $variants->each(fn (ProductVariant $variant) => $this->syncImportantSizeLowStockForVariant($variant)));
    }

    public function syncImportantSizeLowStockForVariant(ProductVariant $variant): void
    {
        if (in_array((string) $variant->size, (array) config('services.admin.important_sizes', ['40', '41']), true) && (int) $variant->stock <= (int) ($variant->low_stock_threshold ?? config('services.admin.low_stock_threshold', 5))) {
            $this->notifyOnce('important_size_low_stock', 'Size quan trọng sắp hết hàng', $variant->sku, ['variant_id' => $variant->id], $variant);
        }
    }
}
