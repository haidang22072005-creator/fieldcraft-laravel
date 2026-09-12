<?php

namespace App\Support;

final class OrderStatus
{
    public const PENDING = 'pending';
    public const PENDING_PAYMENT = 'pending_payment';
    public const CONFIRMED = 'confirmed';
    public const PACKING = 'packing';
    public const PREPARING = 'preparing';
    public const SHIPPING = 'shipping';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public static function all(): array
    {
        return [self::PENDING, self::PENDING_PAYMENT, self::CONFIRMED, self::PACKING, self::PREPARING, self::SHIPPING, self::COMPLETED, self::CANCELLED];
    }

    public static function customerCancellable(): array
    {
        return [self::PENDING, self::PENDING_PAYMENT, self::PREPARING, self::CONFIRMED, self::PACKING, self::SHIPPING];
    }

    public static function adminTargets(): array
    {
        return [self::PENDING, self::CONFIRMED, self::PACKING, self::SHIPPING, self::CANCELLED];
    }

    public static function transitionsFrom(string $status): array
    {
        return match ($status) {
            self::PENDING => [self::CONFIRMED],
            self::PENDING_PAYMENT => [self::PENDING],
            self::CONFIRMED => [self::PACKING],
            self::PACKING, self::PREPARING => [self::SHIPPING],
            self::SHIPPING => [self::COMPLETED],
            default => [],
        };
    }

    public static function canTransition(string $from, string $to): bool
    {
        return $from === $to || in_array($to, self::transitionsFrom($from), true);
    }
}
