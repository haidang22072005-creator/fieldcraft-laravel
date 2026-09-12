<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntelligenceController extends Controller
{
    public function __construct(private AdminIntelligenceService $intelligence) {}

    public function dashboard(): JsonResponse { return response()->json($this->intelligence->dashboard()); }

    public function revenue(Request $request): JsonResponse
    {
        $range = $request->validate(['range' => ['nullable', 'in:7,30,3,3m,12,12m']])['range'] ?? 30;
        return response()->json($this->intelligence->revenue($range));
    }

    public function opsRadar(): JsonResponse { return response()->json(['data' => $this->intelligence->opsRadar()->values()]); }
    public function finance(): JsonResponse { return response()->json($this->intelligence->finance()); }
    public function inventory(): JsonResponse { return response()->json(['data' => $this->intelligence->inventoryMatrix()]); }
    public function restock(): JsonResponse { return response()->json(['data' => $this->intelligence->restockRadar()]); }
    public function performance(): JsonResponse { return response()->json(['data' => $this->intelligence->productPerformance()]); }

    public function customer360(User $user): JsonResponse
    {
        abort_unless($user->role === 'customer', 404);
        return response()->json(['data' => $this->intelligence->customer360($user)]);
    }
}
