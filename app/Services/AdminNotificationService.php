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
        $payload = array_merge($data, $subject ? ['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()] : []);
        $eventKey = hash('sha256', implode('|', [$type, $body ?? '', $subject?->getMorphClass() ?? '', (string) ($subject?->getKey() ?? '')]));
        User::query()->whereIn('role', ['admin', 'super-admin'])->pluck('id')->each(function (int $id) use ($eventKey, $type, $title, $body, $payload): void {
            $key = $eventKey.'-'.$id;
            if (AdminNotification::query()->where('user_id', $id)->where(function ($query) use ($key, $type, $body): void {
                $query->where('event_key', $key)->orWhere(function ($legacy) use ($type, $body): void {
                    $legacy->whereNull('event_key')->where('type', $type)->where('body', $body);
                });
            })->exists()) return;
            AdminNotification::query()->firstOrCreate(['event_key' => $key], ['user_id' => $id, 'type' => $type, 'title' => $title, 'body' => $body, 'data' => $payload]);
        });
    }

    public function notifyUserOnce(User $user, string $type, string $title, ?string $body = null, array $data = [], ?Model $subject = null): void
    {
        $payload = array_merge($data, $subject ? ['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()] : []);
        $eventKey = hash('sha256', implode('|', [$user->id, $type, $body ?? '', $subject?->getMorphClass() ?? '', (string) ($subject?->getKey() ?? '')]));

        AdminNotification::query()->firstOrCreate(
            ['event_key' => $eventKey],
            ['user_id' => $user->id, 'type' => $type, 'title' => $title, 'body' => $body, 'data' => $payload]
        );
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
