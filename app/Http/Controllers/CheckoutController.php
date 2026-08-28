<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Services\CartManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private CartManager $cart) {}

    public function create(Request $request): View|RedirectResponse
    {
        $items = $this->cart->items($request);
        if ($items->isEmpty()) return redirect()->route('store.home')->with('cart_error', 'Giỏ hàng đang trống.');
        if ($items->contains(fn (array $line) => ! $line['available'])) return redirect()->route('cart.index')->withErrors(['cart' => 'Tồn kho đã thay đổi. Vui lòng chỉnh lại số lượng trước khi thanh toán.']);
        return view('checkout', compact('items'));
    }

    public function store(Request $request, CreateOrder $createOrder): RedirectResponse
    {
        $input = $request->validate(['name'=>['required','string','max:100'],'phone'=>['required','string','max:25'],'address'=>['required','string','max:255'],'payment_method'=>['required','in:cod,online'],'coupon'=>['nullable','string','max:30']]);
        $items = $this->cart->items($request);
        if ($items->isEmpty()) return redirect()->route('store.home');
        if ($items->contains(fn (array $line) => ! $line['available'])) return redirect()->route('cart.index')->withErrors(['cart' => 'Tồn kho đã thay đổi. Vui lòng chỉnh lại số lượng.']);
        $order = $createOrder->handle($items->map(fn ($line) => ['product_variant_id'=>$line['variant']->id,'quantity'=>$line['quantity']]), ['user_id'=>$request->user()?->id,'payment_method'=>$input['payment_method'],'payment_status'=>'pending','status'=>'pending','shipping_fee'=>0,'coupon_code'=>$input['coupon'] ?? null]);
        $this->cart->clear($request);
        return redirect()->route('store.home')->with('success', "Đặt hàng thành công. Mã đơn: {$order->number}");
    }

    public function purchases(Request $request): View
    {
        return view('purchases', ['orders' => $request->user()->orders()->with('items')->latest()->get()]);
    }
}
