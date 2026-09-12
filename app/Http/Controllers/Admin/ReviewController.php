<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected,hidden'], 'q' => ['nullable', 'string', 'max:100']]);
        $query = Review::with(['user', 'product', 'order', 'adminRepliedBy'])->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->when($filters['q'] ?? null, function ($q, $term): void { $q->where(fn ($q) => $q->where('comment', 'like', "%{$term}%")->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"))->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))); });
        return view('admin.reviews.index', [
            'reviews' => $query->latest()->paginate(20)->withQueryString(), 'filters' => $filters,
        ]);
    }

    public function status(Request $request, Review $review): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:pending,approved,rejected,hidden']])['status'];
        $review->update(['status' => $status]);
        app(\App\Services\ActivityLogService::class)->record('review.status_changed', $review, ['status' => $status], $request->user()->id);

        return back()->with('success', 'Đã cập nhật trạng thái đánh giá.');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $reply = $request->validate(['reply' => ['required', 'string', 'max:2000']])['reply'];
        $review->update([
            'admin_reply' => $reply,
            'admin_replied_by' => $request->user()->id,
            'admin_replied_at' => now(),
        ]);
        app(\App\Services\ActivityLogService::class)->record('review.replied', $review, [], $request->user()->id);

        return back()->with('success', 'Đã lưu phản hồi FIELDCRAFT.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();
        app(\App\Services\ActivityLogService::class)->record('review.deleted', $review, [], $request->user()->id);

        return back()->with('success', 'Đã xóa đánh giá vi phạm.');
    }
}
