<?php

namespace App\Services;

use App\Models\CrossSellRule;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class CrossSellService
{
    public function recommend(?Product $product = null, ?string $category = null, ?string $brand = null): Collection
    {
        $rules = CrossSellRule::query()->where('is_active', true)->when($product, fn ($q) => $q->where(function ($q) use ($product): void {
            $q->where(fn ($q) => $q->where('source_type', 'product')->where('source_value', (string) $product->id))
                ->orWhere(fn ($q) => $q->where('source_type', 'category')->where('source_value', $product->category))
                ->orWhere(fn ($q) => $q->where('source_type', 'brand')->where('source_value', (string) $product->brand));
        }))->when(! $product && $category, fn ($q) => $q->where('source_type', 'category')->where('source_value', $category))->when(! $product && ! $category && $brand, fn ($q) => $q->where('source_type', 'brand')->where('source_value', $brand))->with(['recommendedProduct.variants'])->orderByDesc('priority')->get();
        $products = $rules->map->recommendedProduct->filter(fn (?Product $item) => $item && $item->is_active && $item->variants->contains(fn (ProductVariant $variant) => $variant->stock > 0))->unique('id')->values();
        if ($products->isEmpty() && $product) {
            $products = Product::query()->where('is_active', true)->where('id', '!=', $product->id)->where(function ($q) use ($product): void { $q->where('category', $product->category)->orWhere(fn ($q) => $q->whereNotNull('brand')->where('brand', $product->brand)); })->with('variants')->get()->filter(fn (Product $item) => $item->variants->contains(fn (ProductVariant $variant) => $variant->stock > 0))->values();
        }
        return $products;
    }
}
