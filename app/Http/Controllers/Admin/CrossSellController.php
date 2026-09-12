<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrossSellRule;
use App\Models\Product;
use App\Services\CrossSellService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrossSellController extends Controller
{
    public function index(): JsonResponse { return response()->json(['data' => CrossSellRule::with('recommendedProduct')->latest()->get()]); }
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['source_type' => ['required', 'in:product,category,brand'], 'source_value' => ['required', 'string', 'max:150'], 'recommended_product_id' => ['required', 'exists:products,id'], 'priority' => ['nullable', 'integer', 'min:0', 'max:999'], 'is_active' => ['nullable', 'boolean']]);
        $rule = CrossSellRule::updateOrCreate(collect($data)->only(['source_type', 'source_value', 'recommended_product_id'])->all(), $data);
        return response()->json(['data' => $rule->load('recommendedProduct')], 201);
    }
    public function destroy(CrossSellRule $crossSellRule): JsonResponse { $crossSellRule->delete(); return response()->json(['deleted' => true]); }
    public function recommend(Request $request, CrossSellService $service): JsonResponse
    {
        $data = $request->validate(['product_id' => ['nullable', 'exists:products,id'], 'category' => ['nullable', 'string', 'max:100'], 'brand' => ['nullable', 'string', 'max:100']]);
        abort_unless($data['product_id'] ?? $data['category'] ?? $data['brand'] ?? false, 422, 'Cần có sản phẩm, danh mục hoặc thương hiệu.');
        return response()->json(['data' => $service->recommend(isset($data['product_id']) ? Product::find($data['product_id']) : null, $data['category'] ?? null, $data['brand'] ?? null)]);
    }
}
