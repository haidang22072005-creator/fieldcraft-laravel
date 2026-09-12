<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\ActivityLogService;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate(['status' => ['nullable', 'in:'.implode(',', SupportTicket::STATUSES)], 'category' => ['nullable', 'in:'.implode(',', SupportTicket::CATEGORIES)], 'priority' => ['nullable', 'in:normal,high']]);
        $tickets = SupportTicket::query()->with(['user', 'order'])->withCount('messages')->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))->when($filters['category'] ?? null, fn ($q, $value) => $q->where('category', $value))->when($filters['priority'] ?? null, fn ($q, $value) => $q->where('priority', $value))->latest('last_message_at')->paginate(30);

        return response()->json($tickets);
    }

    public function show(SupportTicket $supportTicket): JsonResponse
    {
        return response()->json(['data' => $supportTicket->load(['user', 'order', 'messages.sender'])]);
    }

    public function reply(Request $request, SupportTicket $supportTicket, AdminNotificationService $notifications, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $supportTicket, $data): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($supportTicket->id);
            if ($ticket->status === 'closed') throw ValidationException::withMessages(['ticket' => 'Yêu cầu đã đóng, hãy mở lại trước khi trả lời.']);
            $ticket->messages()->create(['sender_user_id' => $request->user()->id, 'sender_role' => $request->user()->role, 'message' => $data['message']]);
            $ticket->update(['last_message_at' => now(), 'status' => 'in_progress']);
        });

        $fresh = $supportTicket->fresh()->load(['user', 'order', 'messages.sender']);
        $notifications->notifyUserOnce($fresh->user, 'support_admin_message', 'Bạn có phản hồi hỗ trợ mới', $fresh->subject, ['ticket_id' => $fresh->id], $fresh);
        $activity->record('support.message_created', $fresh, ['sender_role' => 'admin'], $request->user()->id);

        return response()->json(['data' => $fresh]);
    }

    public function status(Request $request, SupportTicket $supportTicket, ActivityLogService $activity): JsonResponse
    {
        $newStatus = $request->validate(['status' => ['required', 'in:'.implode(',', SupportTicket::STATUSES)]])['status'];
        DB::transaction(function () use ($supportTicket, $newStatus): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($supportTicket->id);
            $allowed = match ($ticket->status) {
                'open' => ['in_progress', 'resolved', 'closed'],
                'in_progress' => ['open', 'resolved', 'closed'],
                'resolved' => ['in_progress', 'closed'],
                'closed' => ['open'],
                default => [],
            };
            if ($ticket->status !== $newStatus && ! in_array($newStatus, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'Trạng thái yêu cầu không thể chuyển theo quy trình.']);
            }
            $ticket->update(['status' => $newStatus, 'closed_at' => $newStatus === 'closed' ? ($ticket->closed_at ?? now()) : null]);
        });

        $fresh = $supportTicket->fresh();
        $activity->record('support.status_changed', $fresh, ['status' => $newStatus], $request->user()->id);

        return response()->json(['data' => $fresh]);
    }
}
