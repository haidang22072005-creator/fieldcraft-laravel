<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private function items(Request $request): Collection
    {
        return collect($request->session()->get('cart', []))->map(function (array $line) {
            $variant = ProductVariant::with('product')->find($line['product_variant_id']);
            return $variant ? ['variant' => $variant, 'quantity' => $line['quantity']] : null;
        })->filter()->values();
    }

    public function create(Request $request): View|RedirectResponse
    {
        $items = $this->items($request);
        if ($items->isEmpty()) return redirect()->route('store.home')->with('cart_error', 'Giỏ hàng đang trống.');
        return view('checkout', compact('items'));
    }

    public function store(Request $request, CreateOrder $createOrder): RedirectResponse
    {
        $input = $request->validate(['name'=>['required','string','max:100'],'phone'=>['required','string','max:25'],'address'=>['required','string','max:255'],'payment_method'=>['required','in:cod,online'],'coupon'=>['nullable','string','max:30']]);
        $items = $this->items($request);
        if ($items->isEmpty()) return redirect()->route('store.home');
        $order = $createOrder->handle($items->map(fn ($line) => ['product_variant_id'=>$line['variant']->id,'quantity'=>$line['quantity']]), ['user_id'=>$request->user()?->id,'payment_method'=>$input['payment_method'],'payment_status'=>'pending','status'=>'pending','shipping_fee'=>0,'coupon_code'=>$input['coupon'] ?? null]);
        $request->session()->forget('cart');
        return redirect()->route('store.home')->with('success', "Đặt hàng thành công. Mã đơn: {$order->number}");
    }

    public function purchases(Request $request): View
    {
        return view('purchases', ['orders' => $request->user()->orders()->with('items')->latest()->get()]);
    }
}
