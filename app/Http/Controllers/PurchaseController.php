<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrder;
use App\Models\Order;
use App\Services\CartManager;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\OrderStatus;

class PurchaseController extends Controller
{
    public function __construct(
        private CartManager $cart,
        private GHNOrderService $ghnOrders,
    ) {}

    public function show(Request $request, Order $order): View|JsonResponse
    {
        Gate::authorize('view', $order);
        $this->loadOrder($order);
        $tracking = $this->ghnOrders->trackingForOrder($order);
        $data = $this->orderData($order, $tracking);

        if ($request->expectsJson()) {
            return response()->json(['order' => $data, 'tracking' => $tracking]);
        }

        return view('purchase-detail', compact('order', 'tracking'));
    }

    public function tracking(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return response()->json(['tracking' => $this->ghnOrders->trackingForOrder($order)]);
    }

    public function reorder(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $order);
        $order->load('items.variant');
        $cartItems = $this->cart->items($request)->keyBy(fn (array $line) => (int) $line['variant']->id);
        $added = [];
        $skipped = [];

        foreach ($order->items as $item) {
            $variant = $item->variant;
            if (! $variant || $variant->stock < 1) {
                $skipped[] = $item->product_name;
                continue;
            }

            $currentQuantity = (int) ($cartItems->get($variant->id)['quantity'] ?? 0);
            $quantity = min((int) $item->quantity, max(0, (int) $variant->stock - $currentQuantity));
            if ($quantity < 1) {
                $skipped[] = $item->product_name;
                continue;
            }

            try {
                $this->cart->add($request, $variant, $quantity);
                $added[] = $item->product_name;
            } catch (ValidationException) {
                $skipped[] = $item->product_name;
            }
        }

        $message = count($added) > 0
            ? 'Đã thêm sản phẩm có thể mua lại vào giỏ hàng.'.(count($skipped) ? ' Một số sản phẩm đã hết hàng hoặc không đủ tồn kho.' : '')
            : 'Không có sản phẩm nào còn đủ tồn kho để mua lại.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'added' => $added, 'skipped' => $skipped]);
        }

        return redirect()->route('cart.index')->with('success', $message);
    }

    public function cancel(Request $request, Order $order, CancelOrder $cancelOrder): RedirectResponse|JsonResponse
    {
        Gate::authorize('cancel', $order);
        $cancelOrder->handle($order);
        $message = 'Đã hủy đơn hàng.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'remote_cancelled' => true]);
        }

        return redirect()->route('purchases')->with('success', $message);
    }

    public function expedite(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $order);
        if ($order->status === OrderStatus::CANCELLED) {
            throw ValidationException::withMessages(['order' => 'Đơn hàng đã hủy không thể gửi yêu cầu hỗ trợ.']);
        }

        $requested = Order::query()
            ->whereKey($order->id)
            ->whereNull('expedite_requested_at')
            ->update(['expedite_requested_at' => now()]) === 1;
        $message = $requested
            ? 'Đã gửi yêu cầu hỗ trợ giao hàng nhanh hơn.'
            : 'Yêu cầu hỗ trợ giao hàng nhanh hơn đã được gửi trước đó.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'requested' => $requested]);
        }

        return back()->with('success', $message);
    }

    public function print(Request $request, Order $order): View
    {
        Gate::authorize('view', $order);
        $this->loadOrder($order);

        return view('purchase-print', compact('order'));
    }

    private function loadOrder(Order $order): void
    {
        $order->load([
            'items.variant.product.images',
            'items.review',
            'items.customizationJobs',
            'payments',
            'coupon',
            'address',
        ]);
    }

    private function orderData(Order $order, array $tracking): array
    {
        return [
            'number' => $order->number,
            'created_at' => $order->created_at?->toISOString(),
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'shipping_status' => $order->shipping_status,
            'ghn_order_code' => $order->ghn_order_code,
            'recipient' => [
                'name' => $order->recipient_name,
                'phone' => $order->recipient_phone,
                'email' => $order->recipient_email,
            ],
            'address' => [
                'province' => $order->province,
                'district' => $order->district,
                'ward' => $order->ward,
                'line' => $order->address_line,
            ],
            'note' => $order->note,
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'color' => $item->color,
                'size' => $item->size,
                'unit_price' => (int) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'variant' => $item->variant ? [
                    'id' => $item->variant->id,
                    'stock' => (int) $item->variant->stock,
                    'price' => (int) $item->variant->price,
                    'images' => $item->variant->product?->images->map(fn ($image) => [
                        'path' => $image->path,
                        'color' => $image->color,
                    ])->values()->all() ?? [],
                ] : null,
                'review' => $item->review ? [
                    'rating' => (int) $item->review->rating,
                    'comment' => $item->review->comment,
                    'status' => $item->review->status,
                ] : null,
            ])->values()->all(),
            'subtotal' => (int) $order->subtotal,
            'discount' => (int) $order->discount,
            'shipping_fee' => (int) $order->shipping_fee,
            'total' => (int) $order->total,
            'tracking' => $tracking,
        ];
    }
}
