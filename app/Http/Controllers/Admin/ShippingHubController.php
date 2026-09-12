<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ShippingHubService;
use Illuminate\Http\JsonResponse;

class ShippingHubController extends Controller
{
    public function index(ShippingHubService $service): JsonResponse { return response()->json(['data' => $service->summary()]); }
    public function providers(): JsonResponse { return response()->json(['data' => app(\App\Services\ShippingProviderRegistry::class)->providers()]); }
}
