<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data' => ActivityLog::with(['actor', 'subject'])->when($request->action, fn ($q, $action) => $q->where('action', $action))->latest()->paginate($request->integer('per_page', 30))]); }
}
