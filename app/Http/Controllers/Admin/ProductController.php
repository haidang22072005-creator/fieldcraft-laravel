<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', ['products' => Product::with(['images', 'variants'])->latest()->paginate(12)]);
    }

    public function create(): View { return view('admin.products.form', ['product' => new Product]); }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->data($request));
        $this->syncVariants($product, $request);
        $this->storeImages($product, $request);
        return redirect()->route('admin.products.index')->with('success', 'Đã tạo sản phẩm và ảnh thành công.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product->load(['images', 'variants'])]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->data($request, $product));
        $this->syncVariants($product, $request);
        $this->storeImages($product, $request);
        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        foreach ($product->images as $image) {
            if (! Str::startsWith($image->path, 'http')) Storage::disk('public')->delete($image->path);
        }
        $product->delete();
        return back()->with('success', 'Đã xóa sản phẩm.');
    }

    private function data(Request $request, ?Product $product = null): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'brand' => ['nullable', 'string', 'max:100'], 'category' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']]);
        $data['slug'] = Str::slug($data['name']).'-'.($product?->id ?? Str::lower(Str::random(5)));
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    private function syncVariants(Product $product, Request $request): void
    {
        $request->validate(['variants' => ['required', 'array', 'min:1'], 'variants.*.sku' => ['required', 'string', 'max:100'], 'variants.*.color' => ['required', 'string', 'max:100'], 'variants.*.size' => ['required', 'string', 'max:30'], 'variants.*.price' => ['required', 'integer', 'min:0'], 'variants.*.stock' => ['required', 'integer', 'min:0']]);
        $product->variants()->delete();
        $product->variants()->createMany($request->input('variants'));
    }

    private function storeImages(Product $product, Request $request): void
    {
        $request->validate(['images' => ['nullable', 'array', 'max:8'], 'images.*' => ['image', 'max:5120']]);
        $offset = $product->images()->count();
        foreach ($request->file('images', []) as $position => $image) {
            $product->images()->create(['path' => $image->store('products', 'public'), 'position' => $offset + $position]);
        }
    }
}
