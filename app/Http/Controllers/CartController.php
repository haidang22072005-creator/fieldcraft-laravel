<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate(['items' => ['array'], 'items.*.variant_id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'integer', 'min:1']]);
        $variants = ProductVariant::query()->whereIn('id', collect($data['items'] ?? [])->pluck('variant_id'))->get()->keyBy('id');
        $items = collect($data['items'] ?? [])->map(function (array $item) use ($variants) {
            $variant = $variants->get($item['variant_id']);
            abort_unless($variant && $variant->stock >= $item['quantity'], 422, 'Sản phẩm không đủ tồn kho.');
            return ['product_variant_id' => $variant->id, 'quantity' => $item['quantity']];
        })->values()->all();
        $request->session()->put('cart', $items);
        return response()->json(['count' => collect($items)->sum('quantity')]);
    }
}
