<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    private const ONLINE_METHODS = ['momo', 'bank_qr', 'payos', 'online'];

    /**
     * Completed revenue is order-based so multiple payment attempts cannot double count it.
     * COD orders are valid when completed; online orders must be paid and not refunded.
     */
    private function validOrders(array $filters = []): Builder
    {
        return Order::query()
            ->where('status', 'completed')
            ->where('payment_status', '!=', 'refunded')
            ->where(function (Builder $query): void {
                $query->where('payment_method', 'cod')
                    ->orWhere(function (Builder $online): void {
                        $online->whereIn('payment_method', self::ONLINE_METHODS)
                            ->where('payment_status', 'paid');
                    });
            })
            ->whereDoesntHave('payments', fn (Builder $payments) => $payments->where('refund_status', 'refunded'))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('orders.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('orders.created_at', '<=', $date));
    }

    public function build(array $filters = []): array
    {
        $allOrders = Order::query()
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
        $valid = $this->validOrders($filters);
        $validRevenue = (int) (clone $valid)->sum('total');
        $completedOrders = (int) (clone $valid)->count('orders.id');
        $refundedAmount = (int) Order::query()
            ->where(function (Builder $query): void {
                $query->where('payment_status', 'refunded')
                    ->orWhereHas('payments', fn (Builder $payments) => $payments->where('refund_status', 'refunded'));
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->sum('total');

        return [
            'summary' => [
                'total_orders' => (int) $allOrders->count(),
                'completed_orders' => $completedOrders,
                'customer_count' => (int) User::where('role', 'customer')->count(),
                'valid_revenue' => $validRevenue,
                'refunded_amount' => (int) $refundedAmount,
                'average_order_value' => $completedOrders > 0 ? (int) round($validRevenue / $completedOrders) : 0,
            ],
            'categories' => $this->categories($filters),
            'daily' => $this->periods($filters, 'day'),
            'monthly' => $this->periods($filters, 'month'),
            'yearly' => $this->periods($filters, 'year'),
            'payment_methods' => $this->paymentMethods($filters),
        ];
    }

    private function categories(array $filters): array
    {
        return $this->validOrders($filters)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('products', 'product_variants.product_id', '=', 'products.id')
            ->selectRaw("COALESCE(products.category, 'Khác') as category")
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->selectRaw('SUM(order_items.unit_price * order_items.quantity) as revenue')
            ->groupBy('products.category')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row): array => [
                'category' => (string) $row->category,
                'quantity' => (int) $row->quantity,
                'revenue' => (int) $row->revenue,
            ])->values()->all();
    }

    private function periods(array $filters, string $period): array
    {
        $now = now();
        $start = match ($period) {
            'day' => $filters['date_from'] ?? $now->copy()->subDays(29)->toDateString(),
            'month' => $filters['date_from'] ?? $now->copy()->startOfMonth()->subMonths(11)->toDateString(),
            default => $filters['date_from'] ?? $now->copy()->startOfYear()->subYears(4)->toDateString(),
        };
        $end = $filters['date_to'] ?? $now->toDateString();
        $expression = $this->periodExpression($period);

        return $this->validOrders(array_merge($filters, ['date_from' => $start, 'date_to' => $end]))
            ->selectRaw("{$expression} as period")
            ->selectRaw('COUNT(orders.id) as order_count')
            ->selectRaw('SUM(orders.total) as revenue')
            ->groupByRaw($expression)
            ->orderBy('period')
            ->get()
            ->map(fn ($row): array => [
                'period' => (string) $row->period,
                'order_count' => (int) $row->order_count,
                'revenue' => (int) $row->revenue,
            ])->values()->all();
    }

    private function paymentMethods(array $filters): array
    {
        $rows = $this->validOrders($filters)
            ->select('payment_method')
            ->selectRaw('COUNT(orders.id) as order_count')
            ->selectRaw('SUM(orders.total) as revenue')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        return collect(['cod', 'momo', 'bank_qr', 'payos', 'online'])
            ->mapWithKeys(fn (string $method): array => [$method => [
                'order_count' => (int) ($rows->get($method)?->order_count ?? 0),
                'revenue' => (int) ($rows->get($method)?->revenue ?? 0),
            ]])->all();
    }

    private function periodExpression(string $period): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($period) {
                'day' => "strftime('%Y-%m-%d', orders.created_at)",
                'month' => "strftime('%Y-%m', orders.created_at)",
                default => "strftime('%Y', orders.created_at)",
            };
        }

        return match ($period) {
            'day' => "DATE_FORMAT(orders.created_at, '%Y-%m-%d')",
            'month' => "DATE_FORMAT(orders.created_at, '%Y-%m')",
            default => "DATE_FORMAT(orders.created_at, '%Y')",
        };
    }
}
