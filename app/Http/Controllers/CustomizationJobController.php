<?php

namespace App\Http\Controllers;

use App\Models\CustomizationJob;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomizationJobController extends Controller
{
    public function show(Request $request, CustomizationJob $customizationJob): JsonResponse
    { $this->authorize($request, $customizationJob); return response()->json(['data' => $customizationJob->load(['order', 'orderItem', 'customer'])]); }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $isAdmin = in_array($request->user()->role, ['admin', 'super-admin'], true);
        abort_unless($isAdmin || $request->user()->role === 'customer', 403);
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'customization_name' => ['nullable', 'string', 'max:100'],
            'customization_number' => ['nullable', 'string', 'max:20'],
            'customization_notes' => ['nullable', 'string', 'max:2000'],
            'print_name' => ['nullable', 'string', 'max:100'],
            'shirt_number' => ['nullable', 'string', 'max:10'],
            'font' => ['nullable', 'string', 'max:80'],
            'style' => ['nullable', 'string', 'max:80'],
            'print_color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'artwork_path' => ['nullable', 'string', 'max:255'],
        ]);
        $order = Order::query()->findOrFail($data['order_id']);
        if (! $isAdmin && (int) $order->user_id !== (int) $request->user()->id) abort(403);
        if (! $isAdmin && empty($data['order_item_id'])) throw ValidationException::withMessages(['order_item_id' => 'Vui lòng chọn sản phẩm cần customization.']);
        if (! empty($data['order_item_id']) && ! OrderItem::whereKey($data['order_item_id'])->where('order_id', $data['order_id'])->exists()) throw ValidationException::withMessages(['order_item_id' => 'Sản phẩm không thuộc đơn hàng.']);
        if (! $isAdmin && blank($data['customization_name'] ?? null) && blank($data['customization_number'] ?? null) && blank($data['customization_notes'] ?? null)) {
            throw ValidationException::withMessages(['customization' => 'Chỉ tạo yêu cầu khi khách hàng đã nhập nội dung customization.']);
        }
        $data['customer_id'] = $order->user_id;
        $data['print_name'] = $data['print_name'] ?? $data['customization_name'] ?? null;
        $data['shirt_number'] = $data['shirt_number'] ?? $data['customization_number'] ?? null;
        $data['notes'] = $data['notes'] ?? $data['customization_notes'] ?? null;
        if (! empty($data['order_item_id'])) {
            $activeJob = CustomizationJob::query()->where('order_item_id', $data['order_item_id'])->where('status', '!=', 'completed')->exists();
            if ($activeJob) throw ValidationException::withMessages(['customization' => 'Sản phẩm đã có yêu cầu customization đang xử lý.']);
            $item = OrderItem::query()->findOrFail($data['order_item_id']);
            $item->update([
                'customization_name' => $data['customization_name'] ?? $data['print_name'],
                'customization_number' => $data['customization_number'] ?? $data['shirt_number'],
                'customization_notes' => $data['customization_notes'] ?? $data['notes'],
            ]);
        }
        $job = CustomizationJob::create($data)->load(['order', 'orderItem', 'customer']);
        app(\App\Services\ActivityLogService::class)->record('customization.created', $job, [], $request->user()->id);
        app(\App\Services\AdminNotificationService::class)->notify('customization_attention', 'Có yêu cầu customization mới', (string) $job->id, [], $job);
        if ($request->expectsJson()) {
            return response()->json(['data' => $job], 201);
        }

        return redirect()->route('purchases.show', $order)->with('success', 'Đã gửi yêu cầu cá nhân hóa cho sản phẩm.');
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
