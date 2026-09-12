<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelOrder;
use App\Exceptions\GHNException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\AdminIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function dashboard(Request $request, AdminIntelligenceService $intelligence): View
    {
        $range = (int) ($request->validate(['range' => ['nullable', 'in:7,30,12']])['range'] ?? 30);
        $completed = Order::query()->where('status', 'completed');
        $now = now(); $monthStart = $now->copy()->startOfMonth(); $labels = []; $values = [];
        if ($range === 12) {
            $start = $now->copy()->startOfMonth()->subMonths(11);
            $rows = (clone $completed)->where('created_at', '>=', $start)->get(['total', 'created_at'])->groupBy(fn ($o) => $o->created_at->format('Y-m'));
            for ($i = 0; $i < 12; $i++) { $d = $start->copy()->addMonths($i); $labels[] = $d->format('m/Y'); $values[] = (int) $rows->get($d->format('Y-m'), collect())->sum('total'); }
        } else {
            $start = $now->copy()->startOfDay()->subDays($range - 1);
            $rows = (clone $completed)->where('created_at', '>=', $start)->get(['total', 'created_at'])->groupBy(fn ($o) => $o->created_at->format('Y-m-d'));
            for ($i = 0; $i < $range; $i++) { $d = $start->copy()->addDays($i); $labels[] = $d->format('d/m'); $values[] = (int) $rows->get($d->format('Y-m-d'), collect())->sum('total'); }
        }
        return view('admin.dashboard', [
            'intelligence' => $intelligence->dashboard(),
            'revenueIntelligence' => ['7' => $intelligence->revenue(7), '30' => $intelligence->revenue(30), '3m' => $intelligence->revenue('3m'), '12m' => $intelligence->revenue('12m')],
            'opsRadar' => $intelligence->opsRadar(),
            'finance' => $intelligence->finance(),
            'range' => $range, 'chartLabels' => $labels, 'chartValues' => $values,
            'revenue' => (int) $completed->sum('total'), 'monthRevenue' => (int) (clone $completed)->where('created_at', '>=', $monthStart)->sum('total'),
            'ordersCount' => Order::count(), 'pendingOrderCount' => Order::where('status', 'pending')->count(), 'completedOrderCount' => Order::where('status', 'completed')->count(),
            'customerCount' => User::where('role', 'customer')->count(), 'productsCount' => Product::where('is_active', true)->count(), 'pendingReviewCount' => Review::where('status', 'pending')->count(),
            'recentOrders' => Order::with('user')->latest()->take(8)->get(), 'pendingReviews' => Review::with(['user', 'product'])->where('status', 'pending')->latest()->take(5)->get(),
            'lowStockVariants' => ProductVariant::with('product')->where('stock', '<=', 5)->orderBy('stock')->take(8)->get(),
            'statusCounts' => Order::query()->select('status', DB::raw('count(*) as aggregate'))->groupBy('status')->pluck('aggregate', 'status'),
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:pending,confirmed,packing,preparing,shipping,completed,cancelled'], 'payment_method' => ['nullable', 'string', 'max:30'], 'payment_status' => ['nullable', 'string', 'max:30'], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date']]);
        $orders = Order::with(['user', 'items'])->when($filters['q'] ?? null, function ($query, $term): void {
            $query->where(function ($query) use ($term): void { $query->where('number', 'like', "%{$term}%")->orWhere('recipient_name', 'like', "%{$term}%")->orWhere('recipient_phone', 'like', "%{$term}%")->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")); });
        })->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->when($filters['payment_method'] ?? null, fn ($query, $v) => $query->where('payment_method', $v))->when($filters['payment_status'] ?? null, fn ($query, $v) => $query->where('payment_status', $v))->when($filters['date_from'] ?? null, fn ($query, $v) => $query->whereDate('created_at', '>=', $v))->when($filters['date_to'] ?? null, fn ($query, $v) => $query->whereDate('created_at', '<=', $v))->latest()->paginate(20)->withQueryString();
        return view('admin.orders.index', compact('orders', 'filters'));
    }

    public function show(Order $order): View
    { return view('admin.orders.show', ['order' => $order->load(['user', 'address', 'items.variant.product.images', 'payments', 'statusHistories.actor', 'completedBy'])]); }

    public function updateStatus(Request $request, Order $order, GHNService $ghn, GHNOrderService $ghnOrders, CancelOrder $cancelOrder): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:pending,confirmed,packing,shipping,cancelled']])['status'];
        if ($status === 'cancelled') { $code = $cancelOrder->handle($order); if ($code) { try { $ghn->cancelOrder($code); } catch (GHNException) { } } return back()->with('success', 'Đã cập nhật trạng thái đơn.'); }
        if ($status === $order->status) return back()->with('success', 'Trạng thái đơn không thay đổi.');
        $allowed = match ($order->status) { 'pending' => ['confirmed'], 'confirmed' => ['packing'], 'packing', 'preparing' => ['shipping'], default => [] };
        if (! in_array($status, $allowed, true)) throw ValidationException::withMessages(['status' => 'Trạng thái đơn không thể chuyển theo quy trình.']);
        if ($status === 'shipping' && ! $order->ghn_order_code && $ghn->isConfigured() && $order->to_district_id && $order->to_ward_code) {
            try { $ghnOrders->createAndStoreWaybill($order->fresh()); } catch (GHNException $exception) { throw ValidationException::withMessages(['status' => 'Không thể bàn giao đơn hàng cho GHN: '.$exception->getMessage()]); }
        }
        DB::transaction(function () use ($request, $order, $status): void { $locked = Order::lockForUpdate()->findOrFail($order->id); if ($locked->status !== $order->status) throw ValidationException::withMessages(['status' => 'Đơn hàng vừa được cập nhật, vui lòng tải lại.']); $from = $locked->status; $locked->update(['status' => $status]); $this->recordStatus($locked, $from, $status, 'admin', $request->user()->id); });
        return back()->with('success', 'Đã cập nhật trạng thái đơn.');
    }

    public function syncGhn(Order $order, GHNOrderService $ghnOrders): RedirectResponse
    {
        if (! $order->ghn_order_code) throw ValidationException::withMessages(['order' => 'Đơn hàng chưa có mã vận đơn GHN.']); $tracking = $ghnOrders->trackingForOrder($order); if (($tracking['source'] ?? null) !== 'ghn') throw ValidationException::withMessages(['order' => 'Chưa thể đồng bộ trạng thái từ GHN.']); $shippingStatus = (string) ($tracking['normalized_status'] ?? $order->shipping_status);
        DB::transaction(function () use ($order, $shippingStatus): void { $locked = Order::lockForUpdate()->findOrFail($order->id); $updates = ['shipping_status' => $shippingStatus]; $from = null; if ($shippingStatus === 'delivered' && $locked->status !== 'cancelled') { $updates['completed_at'] = $locked->completed_at ?? now(); if ($locked->status !== 'completed') { $updates['status'] = 'completed'; $from = $locked->status; } } $locked->update($updates); if ($from) $this->recordStatus($locked, $from, 'completed', 'ghn', null); });
        return back()->with('success', 'Đã đồng bộ trạng thái GHN.');
    }

    public function manualComplete(Order $order, Request $request): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404); DB::transaction(function () use ($order, $request): void { $locked = Order::lockForUpdate()->findOrFail($order->id); if ($locked->status !== 'cancelled' && $locked->status !== 'completed') { $from = $locked->status; $locked->update(['status' => 'completed', 'shipping_status' => 'delivered', 'completed_at' => $locked->completed_at ?? now(), 'completed_by' => $locked->completed_by ?? $request->user()->id]); $this->recordStatus($locked, $from, 'completed', 'admin', $request->user()->id); } });
        return back()->with('success', 'Đã xác nhận giao thành công (môi trường local/sandbox).');
    }

    private function recordStatus(Order $order, string $from, string $to, string $source, ?int $actor): void
    { if ($from !== $to) $order->statusHistories()->create(['from_status' => $from, 'to_status' => $to, 'source' => $source, 'actor_id' => $actor]); }
}
