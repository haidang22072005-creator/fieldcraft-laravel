<?php

namespace App\Http\Controllers;

use App\Services\ChatMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    public function index(Request $request, ChatMessageService $chat): JsonResponse
    {
        return response()->json(['data' => $chat->customerConversation($request->user())]);
    }

    public function store(Request $request, ChatMessageService $chat): JsonResponse
    {
        $content = $request->validate(['content' => ['required', 'string', 'max:5000']])['content'];
        $message = $chat->sendCustomerMessage($request->user(), $content);

        if (! $message) {
            return response()->json(['message' => 'Hiện chưa có quản trị viên trực tuyến.'], 503);
        }

        return response()->json(['data' => $chat->customerConversation($request->user())], 201);
    }
}
