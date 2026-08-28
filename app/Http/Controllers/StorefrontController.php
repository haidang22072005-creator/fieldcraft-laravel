<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    /** Catalogue fixture; replace with Product::with('variants') after DB setup. */
    private function catalogue(): array
    {
        $databaseProducts = Schema::hasTable('products')
            ? Product::query()->with(['images', 'variants'])->where('is_active', true)->get()
            : collect();
        if ($databaseProducts->isNotEmpty()) {
            return $databaseProducts->map(function (Product $product) {
                $variant = $product->variants->first();
                $path = $product->images->first()?->path;
                return [
                    'id' => $product->id,
                    'variantId' => $variant?->id,
                    'name' => $product->name,
                    'brand' => strtolower((string) $product->brand),
                    'category' => $product->category,
                    'price' => $variant?->price ?? 0,
                    'oldPrice' => null,
                    'badge' => 'Chính hãng',
                    'color' => $variant?->color ?? 'Tiêu chuẩn',
                    'image' => str_starts_with((string) $path, 'http') ? $path : asset('storage/'.$path),
                ];
            })->all();
        }

        return [
            ['id' => 1, 'variantId' => 1, 'name' => 'Adidas F50 Elite FG', 'brand' => 'adidas', 'category' => 'Giày đinh', 'price' => 4890000, 'oldPrice' => 5390000, 'badge' => 'Mới về', 'color' => 'Trắng / Neon', 'image' => 'https://images.unsplash.com/photo-1511886929837-354d827aae26?auto=format&fit=crop&w=900&q=85'],
            ['id' => 2, 'name' => 'Nike Mercurial Vapor 16', 'brand' => 'nike', 'category' => 'Giày đinh', 'price' => 4290000, 'oldPrice' => null, 'badge' => 'Bán chạy', 'color' => 'Đỏ rực', 'image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=85'],
            ['id' => 3, 'name' => 'Áo Messi Inter Miami 25/26', 'brand' => 'adidas', 'category' => 'Áo đấu', 'price' => 1890000, 'oldPrice' => null, 'badge' => 'Số 10', 'color' => 'Hồng', 'image' => 'https://images.unsplash.com/photo-1579952363873-27d3bfad9c0d?auto=format&fit=crop&w=900&q=85'],
            ['id' => 4, 'name' => 'Bóng Adidas League', 'brand' => 'adidas', 'category' => 'Bóng đá', 'price' => 1090000, 'oldPrice' => 1290000, 'badge' => '-15%', 'color' => 'Trắng', 'image' => 'https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=900&q=85'],
            ['id' => 5, 'name' => 'Puma Future 8 Ultimate', 'brand' => 'puma', 'category' => 'Giày đinh', 'price' => 3990000, 'oldPrice' => null, 'badge' => 'Pro pick', 'color' => 'Xanh điện', 'image' => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=900&q=85'],
            ['id' => 6, 'name' => 'Găng tay Predator Pro', 'brand' => 'adidas', 'category' => 'Phụ kiện', 'price' => 1390000, 'oldPrice' => null, 'badge' => 'Goalkeeper', 'color' => 'Đen', 'image' => 'https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=900&q=85'],
        ];
    }

    public function home(): View
    {
        return view('storefront', ['products' => $this->catalogue()]);
    }

    public function products(): JsonResponse
    {
        $products = collect($this->catalogue());
        $query = strtolower((string) request('q', ''));
        $brand = request('brand');
        $category = request('category');
        $color = request('color');
        $max = (int) request('max', 0);

        if ($query !== '') {
            $products = $products->filter(fn (array $product) => str_contains(strtolower($product['name'].' '.$product['category']), $query));
        }
        if ($brand) {
            $products = $products->where('brand', $brand);
        }
        if ($category) {
            $products = $products->where('category', $category);
        }
        if ($color) {
            $products = $products->filter(fn (array $product) => str_contains(strtolower($product['color']), strtolower($color)));
        }
        if ($max > 0) {
            $products = $products->filter(fn (array $product) => $product['price'] <= $max);
        }

        return response()->json(['data' => $products->values()]);
    }
}
