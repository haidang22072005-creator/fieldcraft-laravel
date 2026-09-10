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
        $cartItems = $this->cart->items($request);
        if ($cartItems->contains(fn (array $line) => $line['selection_requested'] && ! $line['available'])) return redirect()->route('cart.index')->withErrors(['cart' => 'Tồn kho đã thay đổi. Vui lòng chỉnh lại số lượng trước khi thanh toán.']);
        $items = $cartItems->where('selected', true)->values();
        if ($items->isEmpty()) return redirect()->route('cart.index')->withErrors(['cart' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.']);
        $user = $request->user();
        $address = $user->addresses()->orderByDesc('is_default')->latest()->first();
        $defaults = [
            'recipient_name' => $address?->recipient_name ?: $user->name,
            'recipient_phone' => $address?->phone ?: $user->phone,
            'recipient_email' => $user->email,
            'province' => $address?->province_code,
            'district' => null,
            'ward' => $address?->ward_code,
            'address_line' => $address?->address_line,
        ];
        return view('checkout', compact('items', 'defaults'));
    }

    public function store(Request $request, CreateOrder $createOrder): RedirectResponse
    {
        $input = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'recipient_phone' => ['required', 'regex:/^0\d{9}$/'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'province' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'ward' => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,online'],
            'coupon' => ['nullable', 'string', 'max:30'],
        ]);
        $cartItems = $this->cart->items($request);
        if ($cartItems->contains(fn (array $line) => $line['selection_requested'] && ! $line['available'])) return redirect()->route('cart.index')->withErrors(['cart' => 'Tồn kho đã thay đổi. Vui lòng chỉnh lại số lượng.']);
        $items = $cartItems->where('selected', true)->values();
        if ($items->isEmpty()) return redirect()->route('cart.index')->withErrors(['cart' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.']);
        $order = $createOrder->handle($items->map(fn ($line) => ['product_variant_id'=>$line['variant']->id,'quantity'=>$line['quantity']]), [
            'user_id' => $request->user()?->id,
            'payment_method' => $input['payment_method'],
            'payment_status' => 'pending',
            'status' => 'pending',
            'shipping_fee' => 0,
            'coupon_code' => $input['coupon'] ?? null,
            'recipient_name' => $input['recipient_name'],
            'recipient_phone' => $input['recipient_phone'],
            'recipient_email' => $input['recipient_email'],
            'province' => $input['province'],
            'district' => $input['district'],
            'ward' => $input['ward'],
            'address_line' => $input['address_line'],
            'note' => $input['note'] ?? null,
        ]);
        $this->cart->removePurchased($request, $items);
        return redirect()->route('store.home')->with('success', "Đặt hàng thành công. Mã đơn: {$order->number}");
    }

    public function purchases(Request $request): View
    {
        return view('purchases', ['orders' => $request->user()->orders()->with('items')->latest()->get()]);
    }
}
