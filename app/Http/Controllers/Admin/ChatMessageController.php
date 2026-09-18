<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ChatMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatMessageController extends Controller
{
    public function page(): View
    {
        return view('admin.chat');
    }

    public function customers(ChatMessageService $chat): JsonResponse
    {
        return response()->json(['data' => $chat->adminCustomers()]);
    }

    public function show(User $user, ChatMessageService $chat): JsonResponse
    {
        $this->ensureCustomer($user);

        return response()->json(['data' => $chat->adminConversation($user)]);
    }

    public function reply(Request $request, User $user, ChatMessageService $chat): JsonResponse
    {
        $this->ensureCustomer($user);
        $content = $request->validate(['content' => ['required', 'string', 'max:5000']])['content'];
        $chat->sendAdminMessage($request->user(), $user, $content);

        return response()->json(['data' => $chat->adminConversation($user)], 201);
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless($user->role === 'customer', 404);
    }
}
