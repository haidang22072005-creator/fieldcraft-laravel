<?php

namespace App\Http\Controllers;

use App\Exceptions\GHNException;
use App\Services\CartManager;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private GHNService $ghn,
        private CartManager $cart,
        private GHNOrderService $ghnOrders,
    ) {}

    public function provinces(): JsonResponse
    {
        try {
            return response()->json(['data' => $this->ghn->getProvinces()]);
        } catch (GHNException) {
            return $this->unavailable();
        }
    }

    public function districts(int $provinceId): JsonResponse
    {
        abort_unless($provinceId > 0, 404);

        try {
            return response()->json(['data' => $this->ghn->getDistricts($provinceId)]);
        } catch (GHNException) {
            return $this->unavailable();
        }
    }

    public function wards(int $districtId): JsonResponse
    {
        abort_unless($districtId > 0, 404);

        try {
            return response()->json(['data' => $this->ghn->getWards($districtId)]);
        } catch (GHNException) {
            return $this->unavailable();
        }
    }

    public function calculateFee(Request $request): JsonResponse
    {
        $input = $request->validate([
            'to_district_id' => ['required', 'integer', 'min:1'],
            'to_ward_code' => ['required', 'string', 'max:20'],
        ]);
        $cartItems = $this->cart->items($request);

        if ($cartItems->contains(fn (array $line) => $line['selection_requested'] && ! $line['available'])) {
            return response()->json(['message' => 'Tồn kho đã thay đổi. Vui lòng chỉnh lại giỏ hàng.'], 422);
        }

        $items = $cartItems->where('selected', true)->values();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Vui lòng chọn ít nhất một sản phẩm.'], 422);
        }

        $weight = $this->ghnOrders->weightForCartItems($items);

        try {
            $feeData = $this->ghn->calculateFee(
                (int) $input['to_district_id'],
                $input['to_ward_code'],
                $weight,
            );
        } catch (GHNException) {
            return $this->unavailable();
        }

        $fee = (int) ($feeData['total'] ?? $feeData['service_fee'] ?? $feeData['fee'] ?? 0);

        return response()->json([
            'shipping_fee' => $fee,
            'weight' => $weight,
            'data' => $feeData,
        ]);
    }

    private function unavailable(): JsonResponse
    {
        return response()->json(['message' => 'Không thể kết nối dịch vụ giao hàng.'], 502);
    }
}
