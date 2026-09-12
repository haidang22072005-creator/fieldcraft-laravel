<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\TeamProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Support\OrderStatus;

class AdminIntelligenceService
{
    public function __construct(private LoyaltyService $loyalty, private CustomerSegmentService $segments) {}
    public function dashboard(): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $month = $now->copy()->startOfMonth();
        $previousDay = $today->copy()->subDay();
        $previousMonth = $month->copy()->subMonth();
        $elapsedToday = $today->diffInSeconds($now);
        $previousDayEnd = $previousDay->copy()->addSeconds($elapsedToday);
        $previousMonthEnd = min(
            $previousMonth->copy()->addSeconds($month->diffInSeconds($now))->timestamp,
            $previousMonth->copy()->endOfMonth()->timestamp,
        );

        // Keep all dashboard order metrics on one aggregate query. Apart from
        // being cheaper, this makes every number use the same point-in-time
        // snapshot when an order is changing state.
        $summary = Order::query()->selectRaw(
            "COUNT(*) AS total_orders,
            SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END) AS total_order_value,
            SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) AS completed_revenue,
            SUM(CASE WHEN status = 'completed' AND created_at >= ? AND created_at <= ? THEN total ELSE 0 END) AS revenue_today,
            SUM(CASE WHEN status = 'completed' AND created_at >= ? AND created_at <= ? THEN total ELSE 0 END) AS revenue_this_month,
            SUM(CASE WHEN status = 'completed' AND created_at >= ? AND created_at <= ? THEN total ELSE 0 END) AS previous_day_revenue,
            SUM(CASE WHEN status = 'completed' AND created_at >= ? AND created_at < ? THEN total ELSE 0 END) AS previous_month_revenue,
            SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS order_count_today,
            SUM(CASE WHEN status IN ('pending', 'pending_payment') THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) AS shipping_order_count,
            AVG(CASE WHEN status = 'completed' THEN total END) AS average_completed_order_value",
            [$today, $now, $month, $now, $previousDay, $previousDayEnd, $previousMonth, Carbon::createFromTimestamp($previousMonthEnd), $today],
        )->first();

        return [
            'total_order_value' => (int) $summary->total_order_value,
            'completed_revenue' => (int) $summary->completed_revenue,
            'revenue_today' => (int) $summary->revenue_today,
            'revenue_this_month' => (int) $summary->revenue_this_month,
            'order_count_today' => (int) $summary->order_count_today,
            'total_orders' => (int) $summary->total_orders,
            'pending_orders' => (int) $summary->pending_orders,
            'completed_orders' => (int) $summary->completed_orders,
            'average_completed_order_value' => (int) ($summary->average_completed_order_value ?? 0),
            'customer_count' => User::where('role', 'customer')->count(),
            'pending_review_count' => Review::where('status', 'pending')->count(),
            'shipping_order_count' => (int) $summary->shipping_order_count,
            'low_stock_variant_count' => $this->lowStockVariants()->count(),
            'trends' => [
                'today_revenue' => $this->trend((int) $summary->revenue_today, (int) $summary->previous_day_revenue),
                'month_revenue' => $this->trend((int) $summary->revenue_this_month, (int) $summary->previous_month_revenue),
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
        $alerts = collect();
        $now = now();
        $stuckAt = $now->copy()->subHours((int) config('services.admin.ops_stuck_hours', 48));
        $add = function (string $type, string $severity, string $title, string $description, string $route, int $id) use (&$alerts, $now): void {
            $alerts->push(['type' => $type, 'severity' => $severity, 'title' => $title, 'description' => $description, 'target_route' => $route, 'entity_id' => $id, 'calculated_at' => $now->toISOString()]);
        };
        Order::where('payment_status', 'paid')->whereNull('ghn_order_code')->where('status', '!=', 'cancelled')->get()->each(fn (Order $order) => $add('paid_without_waybill', 'high', 'Đơn đã thanh toán chưa có vận đơn', $order->number, 'admin.orders.show', $order->id));
        Order::where('status', 'shipping')->where('updated_at', '<', $stuckAt)->get()->each(fn (Order $order) => $add('shipping_stuck', 'high', 'Đơn giao hàng bị treo', $order->number, 'admin.orders.show', $order->id));
        Order::where('shipping_status', 'delivered')->where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->get()->each(fn (Order $order) => $add('ghn_delivered_not_completed', 'high', 'GHN đã giao nhưng đơn chưa hoàn tất', $order->number, 'admin.orders.show', $order->id));
        Payment::where('status', 'failed')->select('order_id', DB::raw('count(*) as failures'))->groupBy('order_id')->having('failures', '>=', 2)->get()->each(fn ($row) => $add('repeated_payment_failures', 'medium', 'Thanh toán thất bại nhiều lần', 'Order ID '.$row->order_id.' có '.$row->failures.' lần thất bại', 'admin.orders.show', (int) $row->order_id));
        Payment::with('order')->whereIn('refund_status', ['required', 'failed'])->get()->each(fn (Payment $payment) => $add('refund_required', 'high', 'Thanh toán cần xử lý hoàn tiền', ($payment->order?->number ?? 'Order #'.$payment->order_id).' · '.number_format((int) $payment->amount).' ₫', 'admin.orders.show', (int) $payment->order_id));
        $this->lowStockVariants()->each(fn (ProductVariant $variant) => $add('low_stock_variant', 'medium', 'Biến thể sắp hết hàng', $variant->product?->name.' · '.$variant->color.' / '.$variant->size, 'admin.products.edit', $variant->product_id));
        ProductVariant::whereIn('size', (array) config('services.admin.important_sizes', ['40', '41']))->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [(int) config('services.admin.low_stock_threshold', 5)])->get()->each(fn (ProductVariant $variant) => $add('important_size_low_stock', 'high', 'Size quan trọng sắp hết hàng', $variant->color.' / size '.$variant->size, 'admin.products.edit', $variant->product_id));
        Review::with('product')->whereIn('status', ['pending', 'approved'])->where('rating', '<=', 2)->whereNull('admin_reply')->get()->each(fn (Review $review) => $add('low_rating_without_reply', 'medium', 'Đánh giá thấp chưa được phản hồi', $review->product?->name ?? 'Review #'.$review->id, 'admin.reviews.index', $review->id));
        Order::whereIn('status', [OrderStatus::PENDING, OrderStatus::PENDING_PAYMENT])->where('created_at', '<', $stuckAt)->get()->each(fn (Order $order) => $add('pending_confirmation', 'medium', 'Đơn chờ xác nhận quá lâu', $order->number, 'admin.orders.show', $order->id));

        return $alerts->values();
    }

    public function finance(): array
    {
        $notCancelled = fn ($q) => $q->where('status', '!=', 'cancelled');
        $latestPayments = Payment::with('order')->latest('id')->get()->unique('order_id')->values()->map(fn (Payment $payment) => ['order_id' => $payment->order_id, 'order_number' => $payment->order?->number, 'provider' => $payment->provider, 'order_payment_status' => $payment->order?->payment_status, 'provider_status' => $payment->status, 'amount' => (int) $payment->amount]);

        $refundRequired = Payment::whereIn('refund_status', ['required', 'pending', 'failed']);

        return ['total_completed_revenue' => (int) Order::where('status', 'completed')->sum('total'), 'online_paid_amount' => (int) Order::where('payment_method', '!=', 'cod')->where('payment_status', 'paid')->sum('total'), 'cod_pending_collection' => (int) Order::where('payment_method', 'cod')->where('payment_status', 'unpaid')->where($notCancelled)->sum('total'), 'cod_collected' => (int) Order::where('payment_method', 'cod')->where('status', 'completed')->sum('total'), 'cancelled_order_value' => (int) Order::where('status', 'cancelled')->sum('total'), 'refund_required_count' => (int) (clone $refundRequired)->count(), 'refund_required_amount' => (int) (clone $refundRequired)->sum('amount'), 'refunded_amount' => (int) Payment::where('refund_status', 'refunded')->sum('amount'), 'reconciliation' => $latestPayments];
    }

    public function inventoryMatrix(): Collection
    {
        return Product::with('variants')->orderBy('name')->get()->map(fn (Product $product) => ['product_id' => $product->id, 'product' => $product->name, 'colors' => $product->variants->groupBy('color')->map(fn (Collection $variants, string $color) => ['color' => $color, 'variants' => $variants->map(fn (ProductVariant $variant) => ['size' => $variant->size, 'sku' => $variant->sku, 'stock' => (int) $variant->stock, 'price' => (int) $variant->price, 'stud_type' => $variant->stud_type, 'foot_shape' => $variant->foot_shape, 'surface_type' => $variant->surface_type, 'low_stock' => $this->isLowStock($variant)])->values()->all()])->values()->all()]);
    }

    public function restockRadar(): Collection
    {
        $since = now()->subDays(30);
        $sales = OrderItem::with('variant')->whereHas('order', fn ($q) => $q->where('status', 'completed')->where('created_at', '>=', $since))->get()->groupBy('product_variant_id');

        return ProductVariant::with('product')->get()->map(function (ProductVariant $variant) use ($sales): array {
            $sold = (int) $sales->get($variant->id, collect())->sum('quantity');
            $velocity = round($sold / 30, 2);
            $low = $this->isLowStock($variant);

            return ['variant_id' => $variant->id, 'product' => $variant->product?->name, 'color' => $variant->color, 'size' => $variant->size, 'stock' => (int) $variant->stock, 'sold_last_30_days' => $sold, 'sales_velocity_per_day' => $velocity, 'low_stock' => $low, 'recommendation' => $low && $sold > 0 ? 'restock_recommended' : ($low || ($velocity > 0 && $variant->stock <= $sold) ? 'watch' : 'normal')];
        });
    }

    public function productPerformance(): Collection
    {
        $last30At = now()->subDays(30);
        $salesByProduct = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.status', 'completed')
            ->get([
                'product_variants.product_id',
                'order_items.color',
                'order_items.size',
                'order_items.unit_price',
                'order_items.quantity',
                'orders.created_at as order_created_at',
            ])->groupBy('product_id');

        return Product::with(['variants', 'approvedReviews'])->get()->map(function (Product $product) use ($salesByProduct, $last30At): array {
            $items = $salesByProduct->get($product->id, collect());
            $last30 = $items->filter(fn ($item) => Carbon::parse($item->order_created_at)->gte($last30At));
            $colors = $items->groupBy('color')->sortByDesc(fn (Collection $values) => $values->sum('quantity'));
            $sizes = $items->groupBy('size')->sortByDesc(fn (Collection $values) => $values->sum('quantity'));

            return [
                'product_id' => $product->id,
                'product' => $product->name,
                'sold_quantity' => (int) $items->sum('quantity'),
                'completed_revenue' => (int) $items->sum(fn ($item) => $item->unit_price * $item->quantity),
                'total_stock' => (int) $product->variants->sum('stock'),
                'average_approved_rating' => $product->approvedReviews->isEmpty() ? null : round((float) $product->approvedReviews->avg('rating'), 2),
                'top_selling_color' => $colors->keys()->first(),
                'top_selling_size' => $sizes->keys()->first(),
                'last_30_day_sales' => (int) $last30->sum('quantity'),
            ];
        });
    }

    public function customer360(User $user): array
    {
        $summary = Order::query()->where('user_id', $user->id)->selectRaw(
            "COUNT(*) AS total_orders,
            SUM(CASE WHEN status = 'completed' AND NOT EXISTS (SELECT 1 FROM payments WHERE payments.order_id = orders.id AND payments.refund_status = 'refunded') THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN status = 'completed' AND NOT EXISTS (SELECT 1 FROM payments WHERE payments.order_id = orders.id AND payments.refund_status = 'refunded') THEN total ELSE 0 END) AS completed_spend,
            AVG(CASE WHEN status = 'completed' AND NOT EXISTS (SELECT 1 FROM payments WHERE payments.order_id = orders.id AND payments.refund_status = 'refunded') THEN total END) AS average_order_value,
            MAX(CASE WHEN status = 'completed' AND NOT EXISTS (SELECT 1 FROM payments WHERE payments.order_id = orders.id AND payments.refund_status = 'refunded') THEN created_at END) AS last_purchase"
        )->first();
        $preferences = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', 'completed')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('payments')->whereColumn('payments.order_id', 'orders.id')->where('refund_status', 'refunded'))
            ->get(['products.brand', 'order_items.size', 'order_items.quantity']);
        $brand = $preferences->groupBy('brand')->sortByDesc(fn (Collection $values) => $values->sum('quantity'))->keys()->first();
        $size = $preferences->groupBy('size')->sortByDesc(fn (Collection $values) => $values->sum('quantity'))->keys()->first();
        $payment = Order::query()->where('user_id', $user->id)->select('payment_method', DB::raw('COUNT(*) AS aggregate'))->groupBy('payment_method')->orderByDesc('aggregate')->value('payment_method');
        $approvedReviewAverage = Review::query()->where('user_id', $user->id)->where('status', 'approved')->avg('rating');
        $loyalty = $this->loyalty->profileFromMetrics(['completed_spend' => $summary->completed_spend, 'completed_order_count' => $summary->completed_orders, 'last_completed_purchase' => $summary->last_purchase, 'loyalty_points' => $user->loyaltyPointTransactions()->sum('points')]);
        $lastPurchase = $summary?->last_purchase ? Carbon::parse($summary->last_purchase)->toISOString() : null;

        return [
            'user_id' => $user->id,
            'total_orders' => (int) ($summary->total_orders ?? 0),
            'completed_orders' => (int) ($summary->completed_orders ?? 0),
            'completed_spend' => (int) ($summary->completed_spend ?? 0),
            'average_order_value' => (int) ($summary->average_order_value ?? 0),
            'last_purchase' => $lastPurchase,
            'preferred_brand' => $brand,
            'common_size' => $size,
            'common_payment_method' => $payment,
            'approved_review_average' => (int) ($approvedReviewAverage ?? 0),
            'loyalty' => $loyalty,
            'segments' => $this->segments->fromMetrics(['completed_order_count' => $summary->completed_orders, 'last_completed_purchase' => $summary->last_purchase], $loyalty['tier']),
            'teams' => TeamProfile::with('members')->where('user_id', $user->id)->latest()->get(),
        ];
    }

    public function lowStockVariants(): Collection
    {
        return ProductVariant::with('product')->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [(int) config('services.admin.low_stock_threshold', 5)])->get();
    }

    private function isLowStock(ProductVariant $variant): bool
    {
        return (int) $variant->stock <= (int) ($variant->low_stock_threshold ?? config('services.admin.low_stock_threshold', 5));
    }

    private function trend(int $current, int $previous): array
    {
        return ['current' => $current, 'previous' => $previous, 'change_percent' => $previous === 0 ? ($current > 0 ? 100 : 0) : round((($current - $previous) / $previous) * 100, 2)];
    }

    private function paymentBucket(?string $method): string
    {
        return match ($method) {
            'cod' => 'cod', 'momo' => 'momo', 'bank_qr', 'payos' => 'bank_qr_payos', default => (string) ($method ?: 'other')
        };
    }
}
