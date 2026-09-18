<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ChatMessageService
{
    public const MAX_HISTORY = 100;

    private const ADMIN_ROLES = ['admin', 'super-admin'];

    public function customerConversation(User $customer): Collection
    {
        $adminIds = $this->adminIds();
        $this->markCustomerMessagesRead($customer, $adminIds);

        return $this->boundedConversation($customer, $adminIds);
    }

    public function adminConversation(User $customer): Collection
    {
        $adminIds = $this->adminIds();
        $this->markAdminMessagesRead($customer, $adminIds);

        return $this->boundedConversation($customer, $adminIds);
    }

    public function adminCustomers(): LengthAwarePaginator
    {
        $adminIds = $this->adminIds();
        $lastMessage = Message::query()
            ->select('content')
            ->where(function ($query) use ($adminIds): void {
                $query->where(function ($query) use ($adminIds): void {
                    $query->whereColumn('messages.sender_id', 'users.id')->whereIn('receiver_id', $adminIds);
                })->orWhere(function ($query) use ($adminIds): void {
                    $query->whereColumn('messages.receiver_id', 'users.id')->whereIn('sender_id', $adminIds);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);
        $lastMessageAt = (clone $lastMessage)->select('created_at');

        return User::query()
            ->where('role', 'customer')
            ->where(function ($query) use ($adminIds): void {
                $query->whereHas('sentMessages', fn ($messages) => $messages->whereIn('receiver_id', $adminIds))
                    ->orWhereHas('receivedMessages', fn ($messages) => $messages->whereIn('sender_id', $adminIds));
            })
            ->select(['id', 'name', 'email', 'avatar'])
            ->selectSub($lastMessage, 'last_message_content')
            ->selectSub($lastMessageAt, 'last_chat_message_at')
            ->withCount(['sentMessages as unread_messages_count' => fn ($messages) => $messages->where('is_read', false)->whereIn('receiver_id', $adminIds)])
            ->orderByDesc('last_chat_message_at')
            ->paginate(30);
    }

    public function sendCustomerMessage(User $customer, string $content): ?Message
    {
        $admin = $this->adminRecipient();
        if (! $admin) {
            return null;
        }

        return Message::create([
            'sender_id' => $customer->id,
            'receiver_id' => $admin->id,
            'content' => $content,
            'is_read' => false,
        ]);
    }

    public function sendAdminMessage(User $admin, User $customer, string $content): Message
    {
        return Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $customer->id,
            'content' => $content,
            'is_read' => false,
        ]);
    }

    public function adminRecipient(): ?User
    {
        return User::query()
            ->whereIn('role', self::ADMIN_ROLES)
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    private function adminIds(): array
    {
        return User::query()->whereIn('role', self::ADMIN_ROLES)->pluck('id')->all();
    }

    private function markCustomerMessagesRead(User $customer, array $adminIds): void
    {
        if ($adminIds === []) {
            return;
        }

        Message::query()
            ->where('receiver_id', $customer->id)
            ->whereIn('sender_id', $adminIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    private function markAdminMessagesRead(User $customer, array $adminIds): void
    {
        if ($adminIds === []) {
            return;
        }

        Message::query()
            ->where('sender_id', $customer->id)
            ->whereIn('receiver_id', $adminIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    private function boundedConversation(User $customer, array $adminIds): Collection
    {
        if ($adminIds === []) {
            return collect();
        }

        return Message::query()
            ->with(['sender:id,name,avatar', 'receiver:id,name,avatar'])
            ->where(function ($query) use ($customer, $adminIds): void {
                $query->where(function ($query) use ($customer, $adminIds): void {
                    $query->where('sender_id', $customer->id)->whereIn('receiver_id', $adminIds);
                })->orWhere(function ($query) use ($customer, $adminIds): void {
                    $query->where('receiver_id', $customer->id)->whereIn('sender_id', $adminIds);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::MAX_HISTORY)
            ->get()
            ->sortBy(fn (Message $message) => sprintf('%010d:%020d', $message->created_at?->getTimestamp() ?? 0, $message->id))
            ->values();
    }
}
