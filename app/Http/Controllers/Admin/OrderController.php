<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function dashboard(): View
    {
        $orders = Order::latest()->take(5)->get();
        return view('admin.dashboard', ['orders' => $orders, 'revenue' => Order::where('status', 'completed')->sum('total'), 'ordersCount' => Order::count(), 'productsCount' => Product::count()]);
    }
    public function index(): View { return view('admin.orders.index', ['orders' => Order::latest()->paginate(20)]); }
    public function updateStatus(Request $request, Order $order): RedirectResponse { $order->update($request->validate(['status' => ['required','in:pending,preparing,shipping,completed,cancelled']])); return back()->with('success','Đã cập nhật trạng thái đơn.'); }
}
