<?php

namespace App\Support;

final class UiLabels
{
    private const ORDER = [
        'pending' => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'packing' => 'Đang đóng gói',
        'preparing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];

    private const PAYMENT_STATUS = [
        'pending_payment' => 'Chờ thanh toán',
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'unpaid' => 'Chưa thanh toán',
        'failed' => 'Thanh toán thất bại',
        'refunded' => 'Đã hoàn tiền',
        'cancelled' => 'Đã hủy',
    ];

    private const PAYMENT_METHOD = [
        'cod' => 'Thanh toán khi nhận hàng',
        'momo' => 'MoMo',
        'online' => 'Thanh toán online',
        'bank_qr' => 'Chuyển khoản ngân hàng',
        'payos' => 'Chuyển khoản payOS',
    ];

    private const GHN = [
        'created' => 'Đã tạo đơn', 'order_created' => 'Đã tạo đơn',
        'ready_to_pick' => 'Chờ lấy hàng', 'picking' => 'Đang lấy hàng',
        'money_collect_picking' => 'Đang thu tiền khi lấy hàng', 'picked' => 'Đã lấy hàng',
        'storing' => 'Đã nhập kho', 'transporting' => 'Đang trung chuyển',
        'sorting' => 'Đang phân loại', 'delivering' => 'Đang giao',
        'money_collect_delivering' => 'Đang giao / thu COD', 'delivered' => 'Giao thành công',
        'delivery_fail' => 'Giao thất bại', 'waiting_to_return' => 'Chờ hoàn hàng',
        'return' => 'Đang hoàn hàng', 'return_transporting' => 'Đang chuyển hoàn',
        'return_sorting' => 'Đang phân loại hoàn', 'returning' => 'Đang hoàn về shop',
        'return_fail' => 'Hoàn hàng thất bại', 'returned' => 'Đã hoàn hàng',
        'cancel' => 'Đã hủy', 'cancelled' => 'Đã hủy',
    ];

    public static function orderStatus(?string $value): string { return self::ORDER[$value ?? ''] ?? self::fallback($value); }

    public static function paymentStatus(?string $value): string { return self::PAYMENT_STATUS[$value ?? ''] ?? self::fallback($value); }

    public static function paymentMethod(?string $value): string { return self::PAYMENT_METHOD[$value ?? ''] ?? self::fallback($value); }

    public static function ghnStatus(?string $value): string { return self::GHN[$value ?? ''] ?? self::fallback($value); }

    private static function fallback(?string $value): string
    { return filled($value) ? ucfirst(str_replace('_', ' ', $value)) : '—'; }
}
