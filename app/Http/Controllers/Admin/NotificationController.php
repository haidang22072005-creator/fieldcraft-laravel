<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, AdminNotificationService $service): JsonResponse { $service->syncImportantSizeLowStock(); return response()->json(['unread_count' => AdminNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count(), 'data' => AdminNotification::where('user_id', $request->user()->id)->latest()->paginate($request->integer('per_page', 30))]); }
    public function read(Request $request, AdminNotification $notification): JsonResponse { abort_unless($notification->user_id === $request->user()->id, 403); $notification->update(['read_at' => $notification->read_at ?? now()]); return response()->json(['data' => $notification]); }
    public function readAll(Request $request): JsonResponse { AdminNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]); return response()->json(['updated' => true]); }
}
