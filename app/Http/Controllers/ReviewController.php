<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order, OrderItem $orderItem): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $order);
        if ($order->status !== 'completed') {
            throw ValidationException::withMessages(['review' => 'Chỉ có thể đánh giá đơn hàng đã hoàn thành.']);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = DB::transaction(function () use ($order, $orderItem, $request, $data): Review {
            $item = $order->items()->with('variant')->lockForUpdate()->findOrFail($orderItem->id);
            $productId = $item->variant?->product_id;
            if (! $productId) {
                throw ValidationException::withMessages(['review' => 'Sản phẩm trong đơn không còn tồn tại.']);
            }

            if (Review::query()->where('user_id', $request->user()->id)->where('order_item_id', $item->id)->exists()) {
                throw ValidationException::withMessages(['review' => 'Bạn đã đánh giá sản phẩm này trong đơn hàng.']);
            }

            return Review::query()->create([
                'user_id' => $request->user()->id,
                'product_id' => $productId,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => 'pending',
            ]);
        });
        app(\App\Services\AdminNotificationService::class)->notify('review_pending', 'Có đánh giá mới chờ duyệt', 'Review #'.$review->id, [], $review);
        app(\App\Services\ActivityLogService::class)->record('review.created', $review, [], $request->user()->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đánh giá đã được gửi và đang chờ duyệt.'], 201);
        }

        return back()->with('success', 'Đánh giá đã được gửi và đang chờ duyệt.');
    }
}
