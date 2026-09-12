<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class KanbanController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $groups = ['pending', 'confirmed', 'packing', 'shipping', 'completed'];
        $orders = Order::query()->whereIn('status', $groups)->with(['user', 'items', 'payments'])->latest()->get()->groupBy('status');
        return response()->json(['data' => collect($groups)->mapWithKeys(fn (string $status) => [$status => $orders->get($status, collect())->values()])]);
    }
}
