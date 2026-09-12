<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Services\ActivityLogService;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()->where('user_id', $request->user()->id)->withCount('messages')->latest('last_message_at')->paginate(20);

        return response()->json($tickets);
    }

    public function store(Request $request, AdminNotificationService $notifications, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:'.implode(',', SupportTicket::CATEGORIES)],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);
        $order = ! empty($data['order_id']) ? Order::query()->whereKey($data['order_id'])->where('user_id', $request->user()->id)->first() : null;
        if (! empty($data['order_id']) && ! $order) {
            throw ValidationException::withMessages(['order_id' => 'Đơn hàng không thuộc tài khoản.']);
        }
        if (blank($data['message'] ?? null)) {
            throw ValidationException::withMessages(['message' => 'Vui lòng nhập nội dung yêu cầu.']);
        }

        $ticket = DB::transaction(function () use ($request, $data): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'user_id' => $request->user()->id,
                'order_id' => $data['order_id'] ?? null,
                'subject' => $data['subject'],
                'category' => $data['category'],
                'status' => 'open',
                'priority' => 'normal',
                'last_message_at' => now(),
            ]);
            $ticket->messages()->create(['sender_user_id' => $request->user()->id, 'sender_role' => 'customer', 'message' => $data['message']]);
            return $ticket;
        });

        $notifications->notify('support_ticket_created', 'Khách hàng tạo yêu cầu hỗ trợ', $ticket->subject, ['ticket_id' => $ticket->id], $ticket);
        $activity->record('support.ticket_created', $ticket, ['category' => $ticket->category], $request->user()->id);

        return response()->json(['data' => $ticket->load(['order', 'messages.sender'])], 201);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        Gate::authorize('view', $supportTicket);

        return response()->json(['data' => $supportTicket->load(['order', 'messages.sender'])]);
    }

    public function reply(Request $request, SupportTicket $supportTicket, AdminNotificationService $notifications, ActivityLogService $activity): JsonResponse
    {
        Gate::authorize('reply', $supportTicket);
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($request, $supportTicket, $data): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($supportTicket->id);
            if ($ticket->status === 'closed') throw ValidationException::withMessages(['ticket' => 'Yêu cầu đã đóng, không thể trả lời.']);
            $ticket->messages()->create(['sender_user_id' => $request->user()->id, 'sender_role' => 'customer', 'message' => $data['message']]);
            $ticket->update(['last_message_at' => now(), 'status' => $ticket->status === 'resolved' ? 'in_progress' : $ticket->status]);
        });

        $fresh = $supportTicket->fresh()->load(['order', 'messages.sender']);
        $notifications->notify('support_customer_message', 'Khách hàng phản hồi hỗ trợ', $fresh->subject, ['ticket_id' => $fresh->id], $fresh);
        $activity->record('support.message_created', $fresh, ['sender_role' => 'customer'], $request->user()->id);

        return response()->json(['data' => $fresh]);
    }
}
