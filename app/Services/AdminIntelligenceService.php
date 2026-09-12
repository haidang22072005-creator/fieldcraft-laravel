<?php

namespace App\Services;

use App\Models\CustomizationJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\TeamProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminIntelligenceService
{
    public function dashboard(): array
    {
        $completed = Order::where('status', 'completed');
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $previousDay = now()->subDay()->startOfDay();
        $previousMonth = now()->subMonth()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        return [
            'total_order_value' => (int) Order::where('status', '!=', 'cancelled')->sum('total'),
            'completed_revenue' => (int) (clone $completed)->sum('total'),
            'revenue_today' => (int) (clone $completed)->where('created_at', '>=', $today)->sum('total'),
            'revenue_this_month' => (int) (clone $completed)->whereBetween('created_at', [$month, $currentMonthEnd])->sum('total'),
            'order_count_today' => Order::where('created_at', '>=', $today)->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::whereIn('status', ['pending', 'pending_payment'])->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'average_completed_order_value' => (int) ((clone $completed)->avg('total') ?? 0),
            'customer_count' => User::where('role', 'customer')->count(),
            'pending_review_count' => Review::where('status', 'pending')->count(),
            'shipping_order_count' => Order::where('status', 'shipping')->count(),
            'low_stock_variant_count' => $this->lowStockVariants()->count(),
            'trends' => [
                'today_revenue' => $this->trend((int) (clone $completed)->where('created_at', '>=', $today)->sum('total'), (int) (clone $completed)->whereBetween('created_at', [$previousDay, $today->copy()->subSecond()])->sum('total')),
                'month_revenue' => $this->trend((int) (clone $completed)->whereBetween('created_at', [$month, $currentMonthEnd])->sum('total'), (int) (clone $completed)->whereBetween('created_at', [$previousMonth, $previousMonthEnd])->sum('total')),
            ],
        ];
    }

    public function revenue(string|int $range = 30): array
    {
        $range = (string) $range;
        $start = match ($range) {
            '7' => now()->startOfDay()->subDays(6),
            '30' => now()->startOfDay()->subDays(29),
            '3m', '3' => now()->startOfMonth()->subMonths(2),
            '12m', '12' => now()->startOfMonth()->subMonths(11),
            default => now()->startOfDay()->subDays(29),
        };
        $groupFormat = in_array($range, ['3m', '3', '12m', '12'], true) ? 'Y-m' : 'Y-m-d';
        $orders = Order::where('status', 'completed')->where('created_at', '>=', $start)->get(['total', 'created_at', 'payment_method', 'payment_status']);
        $grouped = $orders->groupBy(fn (Order $order) => $order->created_at->format($groupFormat));
        $periods = [];
        $cursor = $start->copy();
        $count = in_array($range, ['3m', '3', '12m', '12'], true) ? (int) ($range === '3m' || $range === '3' ? 3 : 12) : (int) $range;
        for ($i = 0; $i < $count; $i++) {
            $key = $cursor->format($groupFormat);
            $bucket = $grouped->get($key, collect());
            $periods[] = ['period' => $key, 'completed_revenue' => (int) $bucket->sum('total'), 'order_count' => $bucket->count(), 'average_order_value' => (int) ($bucket->avg('total') ?? 0)];
            $cursor = $groupFormat === 'Y-m' ? $cursor->addMonth() : $cursor->addDay();
        }

        $paymentBreakdown = $orders->groupBy(fn (Order $order) => $this->paymentBucket($order->payment_method))->map(fn (Collection $bucket) => ['order_count' => $bucket->count(), 'amount' => (int) $bucket->sum('total')])->all();
        return ['range' => $range, 'from' => $start->toISOString(), 'periods' => $periods, 'totals' => ['completed_revenue' => (int) $orders->sum('total'), 'order_count' => $orders->count(), 'average_order_value' => (int) ($orders->avg('total') ?? 0)], 'payment_breakdown' => $paymentBreakdown];
    }

    public function opsRadar(): Collection
    {
        $alerts = collect(); $now = now(); $stuckAt = $now->copy()->subHours((int) config('services.admin.ops_stuck_hours', 48));
        $add = function (string $type, string $severity, string $title, string $description, string $route, int $id) use (&$alerts, $now): void { $alerts->push(['type' => $type, 'severity' => $severity, 'title' => $title, 'description' => $description, 'target_route' => $route, 'entity_id' => $id, 'calculated_at' => $now->toISOString()]); };
        Order::where('payment_status', 'paid')->whereNull('ghn_order_code')->where('status', '!=', 'cancelled')->get()->each(fn (Order $order) => $add('paid_without_waybill', 'high', 'Đơn đã thanh toán chưa có vận đơn', $order->number, 'admin.orders.show', $order->id));
        Order::where('status', 'shipping')->where('updated_at', '<', $stuckAt)->get()->each(fn (Order $order) => $add('shipping_stuck', 'high', 'Đơn giao hàng bị treo', $order->number, 'admin.orders.show', $order->id));
        Order::where('shipping_status', 'delivered')->where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->get()->each(fn (Order $order) => $add('ghn_delivered_not_completed', 'high', 'GHN đã giao nhưng đơn chưa hoàn tất', $order->number, 'admin.orders.show', $order->id));
        Payment::where('status', 'failed')->select('order_id', DB::raw('count(*) as failures'))->groupBy('order_id')->having('failures', '>=', 2)->get()->each(fn ($row) => $add('repeated_payment_failures', 'medium', 'Thanh toán thất bại nhiều lần', 'Order ID '.$row->order_id.' có '.$row->failures.' lần thất bại', 'admin.orders.show', (int) $row->order_id));
        $this->lowStockVariants()->each(fn (ProductVariant $variant) => $add('low_stock_variant', 'medium', 'Biến thể sắp hết hàng', $variant->product?->name.' · '.$variant->color.' / '.$variant->size, 'admin.products.edit', $variant->product_id));
        ProductVariant::whereIn('size', (array) config('services.admin.important_sizes', ['40', '41']))->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [(int) config('services.admin.low_stock_threshold', 5)])->get()->each(fn (ProductVariant $variant) => $add('important_size_low_stock', 'high', 'Size quan trọng sắp hết hàng', $variant->color.' / size '.$variant->size, 'admin.products.edit', $variant->product_id));
        Review::whereIn('status', ['pending', 'approved'])->where('rating', '<=', 2)->whereNull('admin_reply')->get()->each(fn (Review $review) => $add('low_rating_without_reply', 'medium', 'Đánh giá thấp chưa được phản hồi', $review->product?->name ?? 'Review #'.$review->id, 'admin.reviews.index', $review->id));
        Order::whereIn('status', ['pending', 'pending_payment'])->where('created_at', '<', $stuckAt)->get()->each(fn (Order $order) => $add('pending_confirmation', 'medium', 'Đơn chờ xác nhận quá lâu', $order->number, 'admin.orders.show', $order->id));
        return $alerts->values();
    }

    public function finance(): array
    {
        $notCancelled = fn ($q) => $q->where('status', '!=', 'cancelled');
        $latestPayments = Payment::with('order')->latest('id')->get()->unique('order_id')->values()->map(fn (Payment $payment) => ['order_id' => $payment->order_id, 'order_number' => $payment->order?->number, 'provider' => $payment->provider, 'order_payment_status' => $payment->order?->payment_status, 'provider_status' => $payment->status, 'amount' => (int) $payment->amount]);
        return ['total_completed_revenue' => (int) Order::where('status', 'completed')->sum('total'), 'online_paid_amount' => (int) Order::where('payment_method', '!=', 'cod')->where('payment_status', 'paid')->sum('total'), 'cod_pending_collection' => (int) Order::where('payment_method', 'cod')->where('payment_status', 'unpaid')->where($notCancelled)->sum('total'), 'cod_collected' => (int) Order::where('payment_method', 'cod')->where('status', 'completed')->sum('total'), 'cancelled_order_value' => (int) Order::where('status', 'cancelled')->sum('total'), 'refunded_amount' => (int) Order::where('payment_status', 'refunded')->sum('total'), 'reconciliation' => $latestPayments];
    }

    public function inventoryMatrix(): Collection
    {
        return Product::with('variants')->orderBy('name')->get()->map(fn (Product $product) => ['product_id' => $product->id, 'product' => $product->name, 'colors' => $product->variants->groupBy('color')->map(fn (Collection $variants, string $color) => ['color' => $color, 'variants' => $variants->map(fn (ProductVariant $variant) => ['size' => $variant->size, 'sku' => $variant->sku, 'stock' => (int) $variant->stock, 'price' => (int) $variant->price, 'stud_type' => $variant->stud_type, 'foot_shape' => $variant->foot_shape, 'surface_type' => $variant->surface_type, 'low_stock' => $this->isLowStock($variant)])->values()->all()])->values()->all()]);
    }

    public function restockRadar(): Collection
    {
        $since = now()->subDays(30); $sales = OrderItem::with('variant')->whereHas('order', fn ($q) => $q->where('status', 'completed')->where('created_at', '>=', $since))->get()->groupBy('product_variant_id');
        return ProductVariant::with('product')->get()->map(function (ProductVariant $variant) use ($sales): array { $sold = (int) $sales->get($variant->id, collect())->sum('quantity'); $velocity = round($sold / 30, 2); $low = $this->isLowStock($variant); return ['variant_id' => $variant->id, 'product' => $variant->product?->name, 'color' => $variant->color, 'size' => $variant->size, 'stock' => (int) $variant->stock, 'sold_last_30_days' => $sold, 'sales_velocity_per_day' => $velocity, 'low_stock' => $low, 'recommendation' => $low && $sold > 0 ? 'restock_recommended' : ($low || ($velocity > 0 && $variant->stock <= $sold) ? 'watch' : 'normal')]; });
    }

    public function productPerformance(): Collection
    {
        return Product::with(['variants', 'approvedReviews'])->get()->map(function (Product $product): array { $items = OrderItem::with(['variant', 'order'])->whereHas('variant', fn ($q) => $q->where('product_id', $product->id))->whereHas('order', fn ($q) => $q->where('status', 'completed'))->get(); $last30 = $items->filter(fn (OrderItem $item) => $item->order?->created_at?->gte(now()->subDays(30))); $colors = $items->groupBy('color')->sortByDesc(fn ($v) => $v->sum('quantity')); $sizes = $items->groupBy('size')->sortByDesc(fn ($v) => $v->sum('quantity')); return ['product_id' => $product->id, 'product' => $product->name, 'sold_quantity' => (int) $items->sum('quantity'), 'completed_revenue' => (int) $items->sum(fn ($item) => $item->unit_price * $item->quantity), 'total_stock' => (int) $product->variants->sum('stock'), 'average_approved_rating' => $product->approvedReviews->isEmpty() ? null : round((float) $product->approvedReviews->avg('rating'), 2), 'top_selling_color' => $colors->keys()->first(), 'top_selling_size' => $sizes->keys()->first(), 'last_30_day_sales' => (int) $last30->sum('quantity')]; });
    }

    public function customer360(User $user): array
    {
        $orders = $user->orders()->with(['items.variant.product', 'reviews'])->get(); $completed = $orders->where('status', 'completed'); $items = $completed->flatMap->items; $brand = $items->groupBy(fn ($i) => $i->variant?->product?->brand)->sortByDesc(fn ($v) => $v->sum('quantity'))->keys()->first(); $size = $items->groupBy('size')->sortByDesc(fn ($v) => $v->sum('quantity'))->keys()->first(); $payment = $orders->groupBy('payment_method')->sortByDesc->count()->keys()->first(); $reviews = $user->reviews()->where('status', 'approved');
        return ['user_id' => $user->id, 'total_orders' => $orders->count(), 'completed_orders' => $completed->count(), 'completed_spend' => (int) $completed->sum('total'), 'average_order_value' => (int) ($completed->avg('total') ?? 0), 'last_purchase' => $orders->sortByDesc('created_at')->first()?->created_at?->toISOString(), 'preferred_brand' => $brand, 'common_size' => $size, 'common_payment_method' => $payment, 'approved_review_average' => (int) ($reviews->avg('rating') ?? 0), 'teams' => $user->teamProfiles()->with('members')->get()];
    }

    public function lowStockVariants(): Collection
    { return ProductVariant::with('product')->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [(int) config('services.admin.low_stock_threshold', 5)])->get(); }

    private function isLowStock(ProductVariant $variant): bool { return (int) $variant->stock <= (int) ($variant->low_stock_threshold ?? config('services.admin.low_stock_threshold', 5)); }
    private function trend(int $current, int $previous): array { return ['current' => $current, 'previous' => $previous, 'change_percent' => $previous === 0 ? ($current > 0 ? 100 : 0) : round((($current - $previous) / $previous) * 100, 2)]; }
    private function paymentBucket(?string $method): string { return match ($method) { 'cod' => 'cod', 'momo' => 'momo', 'bank_qr', 'payos' => 'bank_qr_payos', default => (string) ($method ?: 'other') }; }
}
