<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartManager
{
    public function ensureCanShop(Request $request): void
    {
        abort_if($request->user() && $request->user()->role !== 'customer', 403, 'Tài khoản quản trị không thể mua hàng.');
    }

    public function items(Request $request): Collection
    {
        $this->ensureCanShop($request);
        $quantities = $request->user()
            ? $this->userCart($request)->items()->pluck('quantity', 'product_variant_id')
            : collect($request->session()->get('cart', []))->pluck('quantity', 'product_variant_id');

        if ($quantities->isEmpty()) {
            return collect();
        }

        $variants = ProductVariant::with(['product.images'])->whereIn('id', $quantities->keys())->get()->keyBy('id');

        return $quantities->map(function (int $quantity, int|string $variantId) use ($variants) {
            $variant = $variants->get((int) $variantId);
            return $variant ? [
                'variant' => $variant,
                'quantity' => $quantity,
                'available' => $variant->stock >= $quantity && $variant->stock > 0,
                'subtotal' => $variant->price * $quantity,
            ] : null;
        })->filter()->values();
    }

    public function count(Request $request): int
    {
        return $this->items($request)->sum('quantity');
    }

    public function add(Request $request, ProductVariant $variant, int $quantity): void
    {
        $this->ensureCanShop($request);
        $this->assertQuantity($variant, $quantity);

        if ($request->user()) {
            $cart = $this->userCart($request);
            $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
            $newQuantity = ($item->exists ? $item->quantity : 0) + $quantity;
            $this->assertQuantity($variant, $newQuantity);
            $item->fill(['quantity' => $newQuantity, 'selected_for_checkout' => true])->save();
            return;
        }

        $items = collect($request->session()->get('cart', []))->keyBy('product_variant_id');
        $newQuantity = (int) ($items->get($variant->id)['quantity'] ?? 0) + $quantity;
        $this->assertQuantity($variant, $newQuantity);
        $items->put($variant->id, ['product_variant_id' => $variant->id, 'quantity' => $newQuantity]);
        $request->session()->put('cart', $items->values()->all());
    }

    public function update(Request $request, ProductVariant $variant, int $quantity): void
    {
        $this->ensureCanShop($request);
        $this->assertQuantity($variant, $quantity);

        if ($request->user()) {
            $this->userCart($request)->items()->updateOrCreate(
                ['product_variant_id' => $variant->id],
                ['quantity' => $quantity, 'selected_for_checkout' => true]
            );
            return;
        }

        $items = collect($request->session()->get('cart', []))->keyBy('product_variant_id');
        abort_unless($items->has($variant->id), 404);
        $items->put($variant->id, ['product_variant_id' => $variant->id, 'quantity' => $quantity]);
        $request->session()->put('cart', $items->values()->all());
    }

    public function adjust(Request $request, ProductVariant $variant, int $delta): void
    {
        $current = $this->items($request)->first(fn (array $line) => $line['variant']->is($variant));
        abort_unless($current, 404);
        $quantity = $current['quantity'] + $delta;
        if ($quantity < 1) {
            $this->remove($request, $variant);
            return;
        }
        $this->update($request, $variant, $quantity);
    }

    public function remove(Request $request, ProductVariant $variant): void
    {
        $this->ensureCanShop($request);
        if ($request->user()) {
            $this->userCart($request)->items()->where('product_variant_id', $variant->id)->delete();
            return;
        }
        $items = collect($request->session()->get('cart', []))->reject(
            fn (array $item) => (int) $item['product_variant_id'] === $variant->id
        );
        $request->session()->put('cart', $items->values()->all());
    }

    public function clear(Request $request): void
    {
        $this->ensureCanShop($request);
        $request->user()
            ? $this->userCart($request)->items()->delete()
            : $request->session()->forget('cart');
    }

    public function mergeGuestCart(Request $request): void
    {
        if (! $request->user() || $request->user()->role !== 'customer') {
            return;
        }
        $guestItems = collect($request->session()->get('cart', []));
        if ($guestItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($request, $guestItems) {
            $cart = $this->userCart($request);
            foreach ($guestItems as $guestItem) {
                $variant = ProductVariant::query()->lockForUpdate()->find($guestItem['product_variant_id']);
                if (! $variant || $variant->stock < 1) continue;
                $item = $cart->items()->lockForUpdate()->firstOrNew(['product_variant_id' => $variant->id]);
                $item->quantity = min($variant->stock, (int) $item->quantity + (int) $guestItem['quantity']);
                $item->selected_for_checkout = true;
                $item->save();
            }
        });
        $request->session()->forget('cart');
    }

    private function userCart(Request $request): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $request->user()->id], ['session_key' => null]);
    }

    private function assertQuantity(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < 1 || $quantity > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Số lượng phải từ 1 đến {$variant->stock}.",
            ]);
        }
    }
}
