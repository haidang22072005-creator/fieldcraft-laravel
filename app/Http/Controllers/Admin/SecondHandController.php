<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecondHandListing;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SecondHandController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data' => SecondHandListing::with(['user', 'reviewer'])->when($request->status, fn ($q, $status) => $q->where('status', $status))->latest()->paginate($request->integer('per_page', 20))]); }
    public function show(SecondHandListing $secondHandListing): JsonResponse { return response()->json(['data' => $secondHandListing->load(['user', 'reviewer'])]); }
    public function status(Request $request, SecondHandListing $secondHandListing, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:submitted,under_review,approved,listed,sold,rejected,cancelled'], 'commission_amount' => ['nullable', 'integer', 'min:0'], 'voucher_bonus' => ['nullable', 'integer', 'min:0'], 'moderation_note' => ['nullable', 'string', 'max:2000']]);
        $updated = \Illuminate\Support\Facades\DB::transaction(function () use ($secondHandListing, $data, $request): SecondHandListing {
            $locked = SecondHandListing::query()->lockForUpdate()->findOrFail($secondHandListing->id);
            $allowed = match ($locked->status) { 'submitted' => ['under_review', 'rejected', 'cancelled'], 'under_review' => ['approved', 'rejected', 'cancelled'], 'approved' => ['listed', 'rejected', 'cancelled'], 'listed' => ['sold', 'cancelled'], default => [] };
            if (! in_array($data['status'], $allowed, true)) throw ValidationException::withMessages(['status' => 'Sản phẩm second-hand không thể chuyển trạng thái theo quy trình.']);
            $updates = collect($data)->except('status')->all() + ['status' => $data['status'], 'reviewed_by' => $request->user()->id];
            if ($data['status'] === 'listed') $updates['listed_at'] = now();
            if ($data['status'] === 'sold') $updates['sold_at'] = now();
            $locked->update($updates);
            return $locked->fresh(['user', 'reviewer']);
        });
        $activity->record('second_hand.status_changed', $updated, ['status' => $data['status']], $request->user()->id);
        return response()->json(['data' => $updated]);
    }
}
