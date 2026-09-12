<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BootPassport;
use App\Services\BootPassportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootPassportController extends Controller
{
    public function index(): JsonResponse { return response()->json(['data' => BootPassport::with(['user', 'order', 'orderItem', 'variant'])->latest()->paginate(30)]); }
    public function show(BootPassport $bootPassport): JsonResponse { return response()->json(['data' => $bootPassport->load(['user', 'order', 'orderItem', 'variant'])]); }
    public function generate(Request $request, BootPassportService $service): JsonResponse { $userId = $request->validate(['user_id' => ['nullable', 'exists:users,id']])['user_id'] ?? null; return response()->json(['generated' => $userId ? count($service->generateForUser((int) $userId)) : $service->generateAll()]); }
}
