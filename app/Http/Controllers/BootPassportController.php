<?php

namespace App\Http\Controllers;

use App\Models\BootPassport;
use App\Services\BootPassportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootPassportController extends Controller
{
    public function index(Request $request): JsonResponse { abort_unless($request->user()->role === 'customer', 403); return response()->json(['data' => $request->user()->bootPassports()->with(['order', 'orderItem', 'variant'])->latest()->get()]); }
    public function show(Request $request, BootPassport $bootPassport): JsonResponse { abort_unless($request->user()->role === 'customer' && $bootPassport->user_id === $request->user()->id, 403); return response()->json(['data' => $bootPassport->load(['order', 'orderItem', 'variant'])]); }
}
