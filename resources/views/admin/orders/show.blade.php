@extends('layouts.admin')

@section('content')
<style>
    /* ── Order Detail UX Styles ── */
    .order-progress-card {
        background: var(--bg-panel);
        border: 1px solid var(--border-panel);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .order-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow-x: auto;
        padding: 10px 4px 4px;
        gap: 12px;
    }
    .order-stepper::before {
        content: '';
        position: absolute;
        top: 24px;
        left: 30px;
        right: 30px;
        height: 2px;
        background: var(--border-panel);
        z-index: 1;
    }
    .step-node {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        position: relative;
        z-index: 2;
        min-width: 100px;
        text-align: center;
    }
    .step-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--bg-panel-sub);
        border: 2px solid var(--border-panel);
        display: flex;
        align-items: center;
        justify-content: center;
        font: 700 11px/1 'DM Mono', monospace;
        color: var(--text-muted);
        transition: all .2s ease;
    }
    .step-node.active .step-circle {
        background: var(--lime);
        border-color: var(--lime);
        color: #07110d;
        box-shadow: 0 0 12px rgba(202, 255, 57, 0.4);
    }
    .step-node.done .step-circle {
        background: var(--bg-panel);
        border-color: var(--lime);
        color: var(--lime);
    }
    .step-name {
        font: 700 11px/1.2 'Oswald', sans-serif;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--text-muted);
    }
    .step-node.active .step-name {
        color: var(--lime);
    }
    .step-node.done .step-name {
        color: var(--text-main);
    }

    /* ── Order Top Stats Grid ── */
    .order-kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .order-kpi {
        background: var(--bg-panel);
        border: 1px solid var(--border-panel);
        border-radius: 8px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .order-kpi-lbl {
        font: 700 10px/1 'DM Mono', monospace;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 8px;
    }
    .order-kpi-val {
        font: 700 20px/1 'Oswald', sans-serif;
        color: var(--text-main);
    }

    /* ── Timeline Rail & Dots ── */
    .timeline-rail {
        position: relative;
        padding-left: 28px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 14px;
    }
    .timeline-rail::before {
        content: '';
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: 7px;
        width: 2px;
        background: var(--border-panel);
    }
    .timeline-entry {
        position: relative;
    }
    .timeline-entry::before {
        content: '';
        position: absolute;
        left: -28px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: var(--lime);
        border: 3px solid var(--bg-panel);
        box-shadow: 0 0 8px rgba(202, 255, 57, 0.4);
    }
    .timeline-entry:not(:first-child)::before {
        background: var(--border-sub);
        box-shadow: none;
    }

    @media (max-width: 1024px) {
        .order-kpis { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .order-kpis { grid-template-columns: 1fr; }
    }
</style>

{{-- Breadcrumbs & Topline --}}
<div class="crumb">VẬN HÀNH / ĐƠN HÀNG / {{ $order->number }}</div>
<div class="topline">
    <div>
        <h1>#{{ $order->number }}</h1>
        <div class="muted">
            Tạo lúc {{ $order->created_at?->format('d/m/Y H:i') }} · Khách hàng: <b>{{ $order->user?->name ?? $order->recipient_name }}</b>
        </div>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.orders.index') }}">← QUAY LẠI DANH SÁCH</a>
        @if($order->ghn_order_code)
            <form method="POST" action="{{ route('admin.orders.sync-ghn', $order) }}">
                @csrf
                <button class="btn" type="submit">🔄 ĐỒNG BỘ GHN</button>
            </form>
        @endif
    </div>
</div>

{{-- Top KPI Cards --}}
<div class="order-kpis">
    <div class="order-kpi">
        <div class="order-kpi-lbl">Tổng thanh toán</div>
        <div class="order-kpi-val lime-text mono">{{ number_format($order->total, 0, ',', '.') }} ₫</div>
        <div class="muted" style="font-size:11px;margin-top:6px">{{ $order->items->count() }} sản phẩm</div>
    </div>
    <div class="order-kpi">
        <div class="order-kpi-lbl">Trạng thái đơn</div>
        <div>
            <span class="status {{ $order->status }}">
                {{ \App\Support\UiLabels::orderStatus($order->status) }}
            </span>
        </div>
        <div class="muted" style="font-size:11px;margin-top:6px">Cập nhật: {{ $order->updated_at?->format('d/m H:i') }}</div>
    </div>
    @php
        $refundLabels = [
            'refund_required' => 'Cần hoàn tiền',
            'refund_pending' => 'Đang xử lý hoàn tiền',
            'refunded' => 'Đã hoàn tiền',
            'refund_failed' => 'Hoàn tiền thất bại',
        ];
        $paymentStatusText = $refundLabels[$order->payment_status] ?? (\App\Support\UiLabels::paymentStatus($order->payment_status) ?: '—');
    @endphp
    <div class="order-kpi">
        <div class="order-kpi-lbl">Thanh toán</div>
        <div>
            <span class="status {{ $order->payment_status }}">
                {{ $paymentStatusText }}
            </span>
        </div>
        <div class="muted" style="font-size:11px;margin-top:6px">{{ \App\Support\UiLabels::paymentMethod($order->payment_method) }}</div>
    </div>
    <div class="order-kpi">
        <div class="order-kpi-lbl">Vận chuyển GHN</div>
        <div>
            <span class="status {{ $order->shipping_status ?? 'muted' }}">
                {{ \App\Support\UiLabels::ghnStatus($order->shipping_status) }}
            </span>
        </div>
        <div class="muted mono" style="font-size:11px;margin-top:6px">
            {{ $order->ghn_order_code ? 'Mã: '.$order->ghn_order_code : 'Chưa tạo mã vận đơn' }}
        </div>
    </div>
</div>

{{-- Order Lifecycle Stepper / Progress Bar --}}
<div class="order-progress-card">
    @if($order->status === 'cancelled')
        <div style="background:var(--danger-bg);border:1px solid var(--danger-border);border-radius:6px;padding:14px 18px;display:flex;align-items:center;gap:12px">
            <span style="font-size:22px">✕</span>
            <div>
                <b style="color:var(--danger);font-size:14px">ĐƠN HÀNG ĐÃ HỦY</b>
                <div class="muted" style="font-size:12px;margin-top:2px">Đơn hàng này đã kết thúc ở trạng thái hủy. Tồn kho và mã giảm giá đã được hoàn trả theo quy định.</div>
            </div>
        </div>
    @else
        @php
            $stages = [
                'pending' => 'Chờ xử lý',
                'confirmed' => 'Đã xác nhận',
                'packing' => 'Đóng gói',
                'shipping' => 'Đang giao',
                'completed' => 'Hoàn tất'
            ];
            $stageKeys = array_keys($stages);
            $currentIndex = array_search($order->status, $stageKeys, true);
            if ($currentIndex === false) {
                // If status is preparing or similar
                $currentIndex = ($order->status === 'preparing') ? 2 : 0;
            }
        @endphp
        <div class="order-stepper">
            @foreach($stages as $key => $label)
                @php
                    $idx = array_search($key, $stageKeys, true);
                    $isDone = ($idx < $currentIndex);
                    $isActive = ($idx === $currentIndex);
                @endphp
                <div class="step-node {{ $isActive ? 'active' : ($isDone ? 'done' : '') }}">
                    <div class="step-circle">{{ $isDone ? '✓' : ($idx + 1) }}</div>
                    <div class="step-name">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- 2-Column Desktop Grid Layout --}}
<div class="dashboard-grid">
    {{-- Left Column: Main Detail Panels --}}
    <div>
        <!-- Products Panel -->
        <section class="panel">
            <div class="toolbar" style="margin-bottom:14px">
                <span class="toolbar-title">📦 Danh sách sản phẩm trong đơn</span>
                <span class="status {{ $order->status }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px"></th>
                            <th>SẢN PHẨM / BIẾN THỂ</th>
                            <th>ĐƠN GIÁ</th>
                            <th style="text-align:right">THÀNH TIỀN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    @php $image = $item->variant?->product?->images?->first(); @endphp
                                    @if($image)
                                        <img class="item-image" src="{{ str_starts_with($image->path, 'http') ? $image->path : asset('storage/'.$image->path) }}" alt="" style="width:46px;height:46px;object-fit:cover;border-radius:4px;border:1px solid var(--border-panel)">
                                    @else
                                        <div style="width:46px;height:46px;background:var(--bg-panel-sub);border-radius:4px;border:1px solid var(--border-panel);display:flex;align-items:center;justify-content:center;font-size:18px">👟</div>
                                    @endif
                                </td>
                                <td>
                                    <b>{{ $item->product_name }}</b>
                                    <div class="muted" style="margin-top:2px;font-size:0.85rem">
                                        {{ $item->variant?->color ?? $item->color ?? '—' }} / {{ $item->variant?->size ?? $item->size ?? '—' }} · SKU: <span class="mono">{{ $item->sku ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="mono">{{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }} ₫</td>
                                <td style="text-align:right">
                                    <b class="mono" style="color:var(--lime)">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }} ₫</b>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Customization Jobs Section (if any) -->
        @if($order->customizationJobs->isNotEmpty())
            @php
                $cStages = [
                    'design_pending' => 'Chờ thiết kế',
                    'customer_approval' => 'Chờ khách duyệt',
                    'approved' => 'Đã duyệt',
                    'printing' => 'Đang in',
                    'quality_check' => 'Kiểm tra QC',
                    'completed' => 'Hoàn tất',
                ];
                $cNext = [
                    'design_pending' => 'customer_approval',
                    'customer_approval' => 'approved',
                    'approved' => 'printing',
                    'printing' => 'quality_check',
                    'quality_check' => 'completed',
                ];
            @endphp
            <section class="panel" style="margin-top:20px">
                <div class="toolbar" style="margin-bottom:14px">
                    <span class="toolbar-title">🎽 Yêu cầu in ấn & Cá nhân hóa ({{ $order->customizationJobs->count() }})</span>
                </div>
                @foreach($order->customizationJobs as $job)
                    <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px;margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px">
                            <div>
                                <b style="color:var(--lime);font-size:15px">In áo: {{ $job->print_name ?: 'Không tên' }} #{{ $job->shirt_number ?: '—' }}</b>
                                <div class="muted" style="font-size:0.85rem;margin-top:2px">
                                    Font: {{ $job->font ?: 'Mặc định' }} · Màu in: {{ $job->print_color ?: 'Mặc định' }} · Kiểu: {{ $job->style ?: 'Tiêu chuẩn' }}
                                </div>
                            </div>
                            <span class="status {{ $job->status === 'completed' ? 'completed' : 'pending' }}">
                                {{ $cStages[$job->status] ?? ($job->status ? ucfirst(str_replace('_', ' ', $job->status)) : 'Khác') }}
                            </span>
                        </div>
                        @if($job->notes)
                            <div style="background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:6px;padding:10px;font-size:0.85rem;margin-bottom:10px">
                                <span class="muted">Ghi chú:</span> {{ $job->notes }}
                            </div>
                        @endif
                        @if(isset($cNext[$job->status]))
                            <form method="POST" action="{{ route('admin.customization-jobs.status', $job) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $cNext[$job->status] }}">
                                <button class="btn small lime" type="submit">
                                    CHUYỂN TIẾP: {{ strtoupper($cStages[$cNext[$job->status]] ?? 'KHÁC') }} →
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        <!-- Status History Timeline -->
        <section class="panel" style="margin-top:20px">
            <span class="toolbar-title">🕒 Lịch sử vòng đời & Chuyển trạng thái</span>
            <div class="timeline-rail">
                @forelse($order->statusHistories->sortBy('created_at') as $event)
                    <div class="timeline-entry">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <b>{{ \App\Support\UiLabels::orderStatus($event->from_status) }}</b>
                            <span class="muted">→</span>
                            <span class="status {{ $event->to_status }}">{{ \App\Support\UiLabels::orderStatus($event->to_status) }}</span>
                        </div>
                        <div class="muted" style="font-size:12px;margin-top:4px">
                            {{ $event->created_at?->format('d/m/Y H:i') }} · Thực hiện: <b>{{ $event->actor?->name ?? strtoupper($event->source ?? 'HỆ THỐNG') }}</b>
                        </div>
                    </div>
                @empty
                    <p class="muted">Chưa có bản ghi lịch sử trạng thái nào.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Right Column: Controls, Customer, and Payment Panels --}}
    <div>
        <!-- Order Actions Panel -->
        <section class="panel">
            <span class="toolbar-title">⚡ Điều phối đơn hàng</span>
            <p class="muted" style="margin:10px 0 16px;font-size:12px">
                Trạng thái hiện tại: <b>{{ \App\Support\UiLabels::orderStatus($order->status) }}</b>
            </p>
            <div class="actions" style="flex-direction:column;align-items:stretch;gap:10px">
                @php
                    $nextMap = ['pending' => 'confirmed', 'confirmed' => 'packing', 'packing' => 'shipping'];
                    $next = $nextMap[$order->status] ?? null;
                @endphp
                @if($next)
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $next }}">
                        <button class="btn lime" type="submit" style="width:100%">
                            CHUYỂN: {{ strtoupper(\App\Support\UiLabels::orderStatus($next)) }} →
                        </button>
                    </form>
                @endif

                @if(in_array($order->status, ['pending', 'confirmed', 'packing'], true))
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button class="btn danger" type="submit" style="width:100%">HUỶ ĐƠN HÀNG</button>
                    </form>
                @endif

                @if(Route::has('admin.orders.manual-complete') && $order->status !== 'completed' && $order->status !== 'cancelled')
                    <form method="POST" action="{{ route('admin.orders.manual-complete', $order) }}" onsubmit="return confirm('Xác nhận khách đã nhận hàng thành công?');">
                        @csrf
                        <button class="btn" type="submit" style="width:100%">✓ XÁC NHẬN GIAO THÀNH CÔNG</button>
                    </form>
                @endif
            </div>
        </section>

        <!-- Recipient Info Panel -->
        <section class="panel" style="margin-top:20px">
            <span class="toolbar-title">👤 Thông tin nhận hàng</span>
            <div style="margin-top:14px;line-height:1.7;font-size:12px">
                <div style="font-size:14px"><b>{{ $order->recipient_name }}</b></div>
                <div class="muted">📞 <span class="mono" style="color:var(--text-main)">{{ $order->recipient_phone }}</span></div>
                @if($order->recipient_email)
                    <div class="muted">📧 {{ $order->recipient_email }}</div>
                @endif
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border-panel)">
                    <div style="color:var(--text-main)">{{ $order->address_line }}</div>
                    <div class="muted">{{ $order->ward }}, {{ $order->district }}, {{ $order->province }}</div>
                </div>
                @if($order->note)
                    <div style="margin-top:10px;padding:10px;background:var(--bg-panel-sub);border-radius:6px;border:1px solid var(--border-panel);font-size:12px">
                        <span class="muted" style="font-weight:700">Ghi chú:</span> {{ $order->note }}
                    </div>
                @endif
            </div>
        </section>

        <!-- Payment & Totals Panel -->
        <section class="panel" style="margin-top:20px">
            <span class="toolbar-title">💳 Thanh toán & Vận đơn</span>
            <div style="margin-top:14px">
                <p class="muted" style="display:flex;justify-content:space-between;margin:8px 0;font-size:12px">
                    <span>Tạm tính</span>
                    <span class="mono" style="color:var(--text-main)">{{ number_format($order->subtotal, 0, ',', '.') }} ₫</span>
                </p>
                @if($order->discount > 0)
                    <p class="muted" style="display:flex;justify-content:space-between;margin:8px 0;font-size:12px">
                        <span>Giảm giá coupon</span>
                        <span class="mono" style="color:var(--danger)">−{{ number_format($order->discount, 0, ',', '.') }} ₫</span>
                    </p>
                @endif
                <p class="muted" style="display:flex;justify-content:space-between;margin:8px 0;font-size:12px">
                    <span>Phí vận chuyển GHN</span>
                    <span class="mono" style="color:var(--text-main)">{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</span>
                </p>

                <hr style="border:none;border-top:1px solid var(--border-panel);margin:12px 0">

                <div style="display:flex;justify-content:space-between;align-items:center;margin:10px 0">
                    <span style="font:700 13px/1 'Oswald',sans-serif;text-transform:uppercase;letter-spacing:.04em">TỔNG CỘNG</span>
                    <b class="mono lime-text" style="font-size:22px">{{ number_format($order->total, 0, ',', '.') }} ₫</b>
                </div>

                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-panel);display:flex;flex-direction:column;gap:10px;font-size:12px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span class="muted">Phương thức:</span>
                        <b>{{ \App\Support\UiLabels::paymentMethod($order->payment_method) }}</b>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span class="muted">Trạng thái:</span>
                        <span class="status {{ $order->payment_status }}">
                            {{ $paymentStatusText }}
                        </span>
                    </div>
                </div>

                @if($order->ghn_order_code)
                    <div style="margin-top:14px;padding:12px;background:var(--bg-panel-sub);border-radius:6px;border:1px solid var(--border-panel)">
                        <div class="muted" style="font:700 10px/1 'DM Mono',monospace;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px">MÃ VẬN ĐƠN GHN</div>
                        <b class="mono lime-text" style="font-size:16px">{{ $order->ghn_order_code }}</b>
                        <div class="muted" style="font-size:11px;margin-top:4px">
                            Trạng thái: <b>{{ \App\Support\UiLabels::ghnStatus($order->shipping_status) }}</b>
                        </div>
                        @if($order->ghn_total_fee)
                            <div class="muted mono" style="font-size:11px;margin-top:2px">
                                Cước GHN thực tế: {{ number_format($order->ghn_total_fee, 0, ',', '.') }} ₫
                            </div>
                        @endif
                    </div>
                @endif

                @if($order->completed_at)
                    <div class="muted" style="margin-top:14px;font-size:11px">
                        ✓ Hoàn tất: {{ $order->completed_at->format('d/m/Y H:i') }}{{ $order->completedBy ? ' · '.$order->completedBy->name : '' }}
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
