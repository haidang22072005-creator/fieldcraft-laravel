<?php

namespace App\Http\Controllers;

use App\Models\CustomizationJob;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomizationJobController extends Controller
{
    public function show(Request $request, CustomizationJob $customizationJob): JsonResponse
    { $this->authorize($request, $customizationJob); return response()->json(['data' => $customizationJob->load(['order', 'orderItem'])]); }

    public function store(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'super-admin'], true), 403);
        $data = $request->validate(['order_id' => ['required', 'integer', 'exists:orders,id'], 'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'], 'print_name' => ['nullable', 'string', 'max:100'], 'shirt_number' => ['nullable', 'string', 'max:10'], 'font' => ['nullable', 'string', 'max:80'], 'style' => ['nullable', 'string', 'max:80'], 'print_color' => ['nullable', 'string', 'max:50'], 'notes' => ['nullable', 'string', 'max:2000'], 'artwork_path' => ['nullable', 'string', 'max:255']]);
        if (! empty($data['order_item_id']) && ! OrderItem::whereKey($data['order_item_id'])->where('order_id', $data['order_id'])->exists()) throw ValidationException::withMessages(['order_item_id' => 'Sản phẩm không thuộc đơn hàng.']);
        $job = CustomizationJob::create($data)->load(['order', 'orderItem']);
        app(\App\Services\ActivityLogService::class)->record('customization.created', $job, [], $request->user()->id);
        app(\App\Services\AdminNotificationService::class)->notify('customization_attention', 'Có yêu cầu customization mới', (string) $job->id, [], $job);
        return response()->json(['data' => $job], 201);
    }

    public function updateStatus(Request $request, CustomizationJob $customizationJob): JsonResponse
    {
        $this->authorize($request, $customizationJob); $newStatus = $request->validate(['status' => ['required', 'in:design_pending,customer_approval,approved,printing,quality_check,completed']])['status']; $from = $customizationJob->status; $admin = in_array($request->user()->role, ['admin', 'super-admin'], true); $allowed = match ($from) { 'design_pending' => ['customer_approval'], 'customer_approval' => ['approved'], 'approved' => ['printing'], 'printing' => ['quality_check'], 'quality_check' => ['completed'], default => [] };
        if (! in_array($newStatus, $allowed, true) || (! $admin && ! ($from === 'customer_approval' && $newStatus === 'approved'))) throw ValidationException::withMessages(['status' => 'Trạng thái công việc in không thể chuyển theo quy trình.']);
        DB::transaction(function () use ($customizationJob, $from, $newStatus): void { $locked = CustomizationJob::lockForUpdate()->findOrFail($customizationJob->id); if ($locked->status !== $from) throw ValidationException::withMessages(['status' => 'Công việc vừa được cập nhật, vui lòng tải lại.']); $locked->update(['status' => $newStatus]); });
        $fresh = $customizationJob->fresh();
        app(\App\Services\ActivityLogService::class)->record('customization.status_changed', $fresh, ['status' => $newStatus], $request->user()->id);
        return response()->json(['data' => $fresh]);
    }

    private function authorize(Request $request, CustomizationJob $job): void
    { abort_unless(in_array($request->user()->role, ['admin', 'super-admin'], true) || (int) $job->order?->user_id === (int) $request->user()->id, 403); }
}
