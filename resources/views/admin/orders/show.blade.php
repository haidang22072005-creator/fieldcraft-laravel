@extends('layouts.admin')

@section('content')
<div class="crumb">VẬN HÀNH / ĐƠN HÀNG / {{ $order->number }}</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">{{ $order->number }}</h1>
        <div class="muted">Tạo lúc {{ $order->created_at?->format('d/m/Y H:i') }} · Khách hàng: {{ $order->user?->name ?? $order->recipient_name }}</div>
    </div>
    <a class="btn" href="{{ route('admin.orders.index') }}">← QUAY LẠI DANH SÁCH</a>
</div>

<div class="dashboard-grid">
    <div>
        <!-- Products Panel -->
        <section class="panel">
            <div class="toolbar" style="margin-bottom:14px">
                <b style="font-size:1.05rem">Sản phẩm trong đơn</b>
                <span class="status {{ $order->status }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span>
            </div>
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
                                <?php $image = $item->variant?->product?->images?->first(); ?>
                                @if($image)
                                    <img class="item-image" src="{{ str_starts_with($image->path, 'http') ? $image->path : asset('storage/'.$image->path) }}" alt="" style="width:46px;height:46px;object-fit:cover;border-radius:4px;border:1px solid var(--border-panel)">
                                @else
                                    <div style="width:46px;height:46px;background:#09150f;border-radius:4px;border:1px solid var(--border-panel)"></div>
                                @endif
                            </td>
                            <td>
                                <b>{{ $item->product_name }}</b>
                                <div class="muted" style="margin-top:2px;font-size:0.85rem">
                                    {{ $item->variant?->color ?? $item->color }} / {{ $item->variant?->size ?? $item->size }} · SKU: {{ $item->sku }}
                                </div>
                            </td>
                            <td>{{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }} ₫</td>
                            <td style="text-align:right">
                                <b style="color:var(--lime)">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }} ₫</b>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <!-- Customization Jobs Section (if any) -->
        @if($order->customizationJobs->isNotEmpty())
            <?php
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
            ?>
            <section class="panel" style="margin-top:20px">
                <div class="toolbar" style="margin-bottom:14px">
                    <b style="font-size:1.05rem">Cá nhân hóa & In ấn ({{ $order->customizationJobs->count() }} yêu cầu)</b>
                </div>
                @foreach($order->customizationJobs as $job)
                    <div style="background:#09150f;border:1px solid var(--border-panel);border-radius:8px;padding:14px;margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:10px">
                            <div>
                                <b style="color:var(--lime)">In ấn: {{ $job->print_name ?: 'Không tên' }} #{{ $job->shirt_number ?: '—' }}</b>
                                <div class="muted" style="font-size:0.82rem;margin-top:2px">
                                    Font: {{ $job->font ?? 'Mặc định' }} · Màu in: {{ $job->print_color ?? 'Mặc định' }} · Kiểu: {{ $job->style ?? 'Tiêu chuẩn' }}
                                </div>
                            </div>
                            <span class="status {{ $job->status === 'completed' ? 'completed' : 'lime' }}">
                                {{ $cStages[$job->status] ?? $job->status }}
                            </span>
                        </div>
                        @if($job->notes)
                            <p class="muted" style="font-size:0.85rem;margin:6px 0">{{ $job->notes }}</p>
                        @endif
                        @if(isset($cNext[$job->status]))
                            <div style="margin-top:10px">
                                <form method="POST" action="{{ route('admin.customization-jobs.status', $job) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $cNext[$job->status] }}">
                                    <button class="btn small lime" type="submit">
                                        CHUYỂN TIẾP: {{ strtoupper($cStages[$cNext[$job->status]]) }} →
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        <!-- Status History Timeline -->
        <section class="panel" style="margin-top:20px">
            <b style="font-size:1.05rem">Lịch sử chuyển trạng thái</b>
            <div class="timeline" style="margin-top:18px">
                @forelse($order->statusHistories->sortBy('created_at') as $event)
                    <article>
                        <b>{{ \App\Support\UiLabels::orderStatus($event->from_status) }} → {{ \App\Support\UiLabels::orderStatus($event->to_status) }}</b>
                        <div class="muted">{{ $event->created_at?->format('d/m/Y H:i') }} · {{ $event->actor?->name ?? strtoupper($event->source) }}</div>
                    </article>
                @empty
                    <p class="muted">Chưa có lịch sử chuyển trạng thái.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div>
        <!-- Order Actions Panel -->
        <section class="panel">
            <b style="font-size:1.05rem">Điều phối đơn hàng</b>
            <p class="muted" style="margin:8px 0 14px">
                Trạng thái: <b>{{ \App\Support\UiLabels::orderStatus($order->status) }}</b> · GHN: <b>{{ \App\Support\UiLabels::ghnStatus($order->shipping_status) }}</b>
            </p>
            <div class="actions" style="flex-wrap:wrap">
                <?php $next = ['pending' => 'confirmed', 'confirmed' => 'packing', 'packing' => 'shipping'][$order->status] ?? null; ?>
                @if($next)
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $next }}">
                        <button class="btn lime" type="submit">CHUYỂN: {{ strtoupper(\App\Support\UiLabels::orderStatus($next)) }}</button>
                    </form>
                @endif
                @if($order->ghn_order_code)
                    <form method="POST" action="{{ route('admin.orders.sync-ghn', $order) }}">
                        @csrf
                        <button class="btn" type="submit">ĐỒNG BỘ GHN</button>
                    </form>
                @endif
                @if(in_array($order->status, ['pending', 'confirmed', 'packing'], true))
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button class="btn danger" type="submit">HUỶ ĐƠN</button>
                    </form>
                @endif
                @if(Route::has('admin.orders.manual-complete'))
                    <form method="POST" action="{{ route('admin.orders.manual-complete', $order) }}">
                        @csrf
                        <button class="btn" type="submit">XÁC NHẬN GIAO THÀNH CÔNG</button>
                    </form>
                @endif
            </div>
        </section>

        <!-- Recipient Info Panel -->
        <section class="panel" style="margin-top:20px">
            <b style="font-size:1.05rem">Thông tin người nhận</b>
            <div style="margin-top:10px;line-height:1.6">
                <div><b>{{ $order->recipient_name }}</b></div>
                <div class="muted">📞 {{ $order->recipient_phone }}</div>
                @if($order->recipient_email)
                    <div class="muted">📧 {{ $order->recipient_email }}</div>
                @endif
                <div style="margin-top:6px;padding-top:6px;border-top:1px solid var(--border-panel)">
                    <div>{{ $order->address_line }}</div>
                    <div class="muted">{{ $order->ward }}, {{ $order->district }}, {{ $order->province }}</div>
                </div>
                @if($order->note)
                    <div style="margin-top:8px;padding:8px;background:#09150f;border-radius:4px;border:1px solid var(--border-panel);font-size:0.85rem">
                        <span class="muted">Ghi chú:</span> {{ $order->note }}
                    </div>
                @endif
            </div>
        </section>

        <!-- Payment & Totals Panel -->
        <section class="panel" style="margin-top:20px">
            <b style="font-size:1.05rem">Tổng tiền & Thanh toán</b>
            <div style="margin-top:12px">
                <p class="muted" style="display:flex;justify-content:space-between;margin:6px 0">
                    <span>Tạm tính</span>
                    <span style="color:var(--text)">{{ number_format($order->subtotal, 0, ',', '.') }} ₫</span>
                </p>
                <p class="muted" style="display:flex;justify-content:space-between;margin:6px 0">
                    <span>Giảm giá coupon</span>
                    <span style="color:#ef4444">−{{ number_format($order->discount, 0, ',', '.') }} ₫</span>
                </p>
                <p class="muted" style="display:flex;justify-content:space-between;margin:6px 0">
                    <span>Phí vận chuyển GHN</span>
                    <span style="color:var(--text)">{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</span>
                </p>
                <hr style="border:none;border-top:1px solid var(--border-panel);margin:12px 0">
                <p style="display:flex;justify-content:space-between;align-items:center;margin:8px 0">
                    <b>TỔNG THANH TOÁN</b>
                    <b style="color:var(--lime);font-size:1.3rem">{{ number_format($order->total, 0, ',', '.') }} ₫</b>
                </p>
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-panel)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <span class="muted">Phương thức:</span>
                        <b>{{ \App\Support\UiLabels::paymentMethod($order->payment_method) }}</b>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span class="muted">Thanh toán:</span>
                        <span class="status">{{ \App\Support\UiLabels::paymentStatus($order->payment_status) }}</span>
                    </div>
                </div>
                @if($order->ghn_order_code)
                    <div style="margin-top:12px;padding:10px;background:#09150f;border-radius:6px;border:1px solid var(--border-panel)">
                        <div class="muted" style="font-size:0.8rem">VẬN ĐƠN GHN</div>
                        <b style="color:var(--lime);font-size:1.05rem">{{ $order->ghn_order_code }}</b>
                        <div class="muted" style="font-size:0.82rem;margin-top:3px">Cước thực tế: {{ number_format($order->ghn_total_fee ?? 0, 0, ',', '.') }} ₫</div>
                    </div>
                @endif
                @if($order->completed_at)
                    <div class="muted" style="margin-top:12px;font-size:0.85rem">
                        Hoàn tất: {{ $order->completed_at->format('d/m/Y H:i') }}{{ $order->completedBy ? ' · '.$order->completedBy->name : '' }}
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
