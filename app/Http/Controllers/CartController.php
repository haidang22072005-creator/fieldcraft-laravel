<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\CartManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartManager $cart) {}

    public function index(Request $request): View
    {
        $items = $this->cart->items($request);
        return view('cart.index', ['items' => $items, 'subtotal' => $items->sum('subtotal')]);
    }

    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['product_variant_id' => ['required', 'integer', 'exists:product_variants,id'], 'quantity' => ['required', 'integer', 'min:1']]);
        $this->cart->add($request, ProductVariant::findOrFail($data['product_variant_id']), $data['quantity']);
        return $this->response($request, 'Đã thêm sản phẩm vào giỏ.');
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $this->cart->update($request, $variant, $data['quantity']);
        return $this->response($request, 'Đã cập nhật giỏ hàng.');
    }

    public function increase(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $this->cart->adjust($request, $variant, 1);
        return $this->response($request, 'Đã tăng số lượng.');
    }

    public function decrease(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $this->cart->adjust($request, $variant, -1);
        return $this->response($request, 'Đã giảm số lượng.');
    }

    public function remove(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $this->cart->remove($request, $variant);
        return $this->response($request, 'Đã xóa sản phẩm khỏi giỏ.');
    }

    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        $this->cart->clear($request);
        return $this->response($request, 'Đã xóa toàn bộ giỏ hàng.');
    }

    private function response(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            $items = $this->cart->items($request);
            return response()->json(['message' => $message, 'count' => $items->sum('quantity'), 'subtotal' => $items->sum('subtotal')]);
        }
        return back()->with('success', $message);
    }
}
