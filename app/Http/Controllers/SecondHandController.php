<?php

namespace App\Http\Controllers;

use App\Models\SecondHandListing;
use App\Services\ActivityLogService;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecondHandController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data' => $request->user()->secondHandListings()->latest()->get()]); }
    public function show(Request $request, SecondHandListing $secondHandListing): JsonResponse { abort_unless($secondHandListing->user_id === $request->user()->id, 403); return response()->json(['data' => $secondHandListing]); }
    public function store(Request $request, ActivityLogService $activity, AdminNotificationService $notifications): JsonResponse
    {
        abort_unless($request->user()->role === 'customer', 403);
        $data = $request->validate(['brand' => ['required', 'string', 'max:100'], 'product_name' => ['required', 'string', 'max:150'], 'size' => ['required', 'string', 'max:30'], 'condition' => ['required', 'string', 'max:80'], 'images' => ['nullable', 'array', 'max:10'], 'images.*' => ['string', 'max:255'], 'asking_price' => ['required', 'integer', 'min:0'], 'payout_method' => ['required', 'in:cash,voucher']]);
        $listing = SecondHandListing::create(array_merge($data, ['user_id' => $request->user()->id, 'status' => 'submitted']));
        $activity->record('second_hand.submitted', $listing, [], $request->user()->id);
        $notifications->notify('second_hand_submission', 'Có sản phẩm second-hand mới', $listing->product_name, [], $listing);
        return response()->json(['data' => $listing], 201);
    }
}
