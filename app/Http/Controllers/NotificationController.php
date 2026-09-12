<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => AdminNotification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->count(),
            'data' => AdminNotification::query()->where('user_id', $request->user()->id)->latest()->paginate(min(50, $request->integer('per_page', 20))),
        ]);
    }

    public function read(Request $request, AdminNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return response()->json(['data' => $notification]);
    }
}
