<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Coupon;
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
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $status = $request->validate(['status' => ['required','in:pending,preparing,shipping,completed,cancelled']])['status'];
        DB::transaction(function () use ($order, $status) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($status === 'cancelled' && $lockedOrder->status !== 'cancelled') {
                $usage = $lockedOrder->couponUsage()->lockForUpdate()->first();
                if ($usage) {
                    $coupon = Coupon::query()->lockForUpdate()->find($usage->coupon_id);
                    $usage->delete();
                    if ($coupon && $coupon->used_count > 0) $coupon->decrement('used_count');
                }
            }
            $lockedOrder->update(['status' => $status]);
        });
        return back()->with('success','Đã cập nhật trạng thái đơn.');
    }
}
