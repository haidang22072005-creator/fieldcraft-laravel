<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ShippingHubService;
use Illuminate\Http\JsonResponse;

class ShippingHubController extends Controller
{
    public function index(\Illuminate\Http\Request $request, ShippingHubService $service): JsonResponse { return response()->json(['data' => $service->summary($request->integer('per_page', 20), max(1, $request->integer('page', 1)))]); }
    public function providers(): JsonResponse { return response()->json(['data' => app(\App\Services\ShippingProviderRegistry::class)->providers()]); }
}
