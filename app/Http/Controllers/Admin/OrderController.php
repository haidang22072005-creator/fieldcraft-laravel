<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\GHNException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Services\GHNService;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function dashboard(): View
    {
        $orders = Order::latest()->take(5)->get();
        return view('admin.dashboard', ['orders' => $orders, 'revenue' => Order::where('status', 'completed')->sum('total'), 'ordersCount' => Order::count(), 'productsCount' => Product::count()]);
    }
    public function index(): View { return view('admin.orders.index', ['orders' => Order::latest()->paginate(20)]); }
    public function updateStatus(Request $request, Order $order, GHNService $ghn): RedirectResponse
    {
        $status = $request->validate(['status' => ['required','in:pending,preparing,shipping,completed,cancelled']])['status'];
        $ghnOrderCode = DB::transaction(function () use ($order, $status) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status === 'cancelled') {
                return null;
            }
            if ($status !== 'cancelled') {
                $lockedOrder->update(['status' => $status]);
                return null;
            }

            foreach ($lockedOrder->items as $item) {
                if (! $item->product_variant_id) {
                    continue;
                }
                $variant = ProductVariant::query()->lockForUpdate()->find($item->product_variant_id);
                $variant?->increment('stock', (int) $item->quantity);
            }

            $usage = $lockedOrder->couponUsage()->lockForUpdate()->first();
            if ($usage) {
                $coupon = Coupon::query()->lockForUpdate()->find($usage->coupon_id);
                $usage->delete();
                if ($coupon && $coupon->used_count > 0) $coupon->decrement('used_count');
            }
            $ghnOrderCode = $lockedOrder->ghn_order_code;
            $lockedOrder->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);
            return $ghnOrderCode;
        });

        if ($ghnOrderCode) {
            try {
                $ghn->cancelOrder($ghnOrderCode);
            } catch (GHNException) {
                // Local cancellation remains authoritative if the remote cancellation fails.
            }
        }

        return back()->with('success','Đã cập nhật trạng thái đơn.');
    }
}
