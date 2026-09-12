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
        }))->when(! $product && $category, fn ($q) => $q->where('source_type', 'category')->where('source_value', $category))->when(! $product && ! $category && $brand, fn ($q) => $q->where('source_type', 'brand')->where('source_value', $brand))->orderByDesc('priority')->orderBy('source_type')->orderBy('id')->limit(100)->get(['recommended_product_id', 'priority']);
        $priorityByProduct = $rules->groupBy('recommended_product_id')->map(fn (Collection $matches) => (int) $matches->max('priority'));
        $productIds = $priorityByProduct->keys()->map(fn ($id) => (int) $id)->all();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->when($product, fn ($query) => $query->where('id', '!=', $product->id))
            ->whereHas('variants', fn ($query) => $query->where('stock', '>', 0))
            ->with(['variants' => fn ($query) => $query->where('stock', '>', 0)])
            ->get()
            ->sortByDesc(fn (Product $item) => $priorityByProduct->get($item->id, 0))
            ->values();
        if ($products->isEmpty() && $product) {
            $products = Product::query()->where('is_active', true)->where('id', '!=', $product->id)->where(function ($q) use ($product): void { $q->where('category', $product->category)->orWhere(fn ($q) => $q->whereNotNull('brand')->where('brand', $product->brand)); })->whereHas('variants', fn ($query) => $query->where('stock', '>', 0))->with(['variants' => fn ($query) => $query->where('stock', '>', 0)])->orderBy('id')->limit(100)->get();
        }
        return $products;
    }
}
