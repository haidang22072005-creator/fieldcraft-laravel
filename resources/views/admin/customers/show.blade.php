@extends('layouts.admin')

@section('content')
@php
    $loyalty = $customer360['loyalty'] ?? ['tier' => 'ROOKIE', 'loyalty_points' => 0, 'progress_percent' => 0, 'next_threshold' => null];
    $segments = $customer360['segments'] ?? [];
    $personalVouchers = $customer360['active_personal_vouchers'] ?? collect();
    $supportTickets = $customer360['recent_support_tickets'] ?? collect();
    $teams = $customer360['teams'] ?? collect();
    $tierLower = strtolower(str_replace(' ', '-', $loyalty['tier'] ?? 'rookie'));
@endphp

<div class="crumb">KHÁCH HÀNG / HỒ SƠ 360° / {{ $customer->name }}</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Hồ sơ khách hàng 360°</h1>
        <div class="muted">Toàn bộ chân dung hành vi mua sắm, giá trị vòng đời và tương tác dịch vụ</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="{{ route('admin.customers.index') }}">← DANH SÁCH KHÁCH HÀNG</a>
    </div>
</div>

@if(session('success'))
    <div class="notice">✓ {{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="errors">
        @foreach($errors->all() as $err)
            <div>⚠️ {{ $err }}</div>
        @endforeach
    </div>
@endif

{{-- Customer 360 Header Panel --}}
<div class="panel" style="margin-bottom:20px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px">
        <div style="display:flex;align-items:center;gap:20px">
            {{-- Avatar or Initials --}}
            <div style="position:relative">
                @if($customer->avatar)
                    <img src="{{ asset('storage/'.$customer->avatar) }}" alt="{{ $customer->name }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--lime);background:#132a1e">
                @else
                    <div style="width:72px;height:72px;border-radius:50%;background:#132a1e;border:3px solid var(--lime);display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:900;color:var(--lime);letter-spacing:1px">
                        {{ strtoupper(mb_substr($customer->name, 0, 2)) }}
                    </div>
                @endif
                <span class="vip-tier-badge {{ $tierLower }}" style="position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);white-space:nowrap;font-size:9px;padding:2px 6px">
                    {{ $loyalty['tier'] }}
                </span>
            </div>

            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <b style="font-size:1.35rem;color:var(--text-main)">{{ $customer->name }}</b>
                    <span class="status" style="background:rgba(202,255,57,0.12);color:var(--lime);border-color:rgba(202,255,57,0.3)">Khách hàng #{{ $customer->id }}</span>
                    @if($customer->email_verified_at)
                        <span class="status completed" style="font-size:10px">✓ Đã xác thực email</span>
                    @else
                        <span class="status pending" style="font-size:10px">Chưa xác thực email</span>
                    @endif
                </div>

                <div class="muted" style="margin-top:6px;font-size:0.88rem;display:flex;gap:18px;flex-wrap:wrap">
                    <span>📧 <b style="color:var(--text-sub)">{{ $customer->email }}</b></span>
                    <span>📞 <b style="color:var(--text-sub)">{{ $orders->first()?->recipient_phone ?? $customer->addresses->first()?->phone ?? 'Chưa có SĐT' }}</b></span>
                    <span>🗓️ Tham gia: <b style="color:var(--text-sub)">{{ $customer->created_at->format('d/m/Y H:i') }}</b></span>
                </div>

                {{-- Segment Tags --}}
                @if(!empty($segments))
                    <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                        <span class="muted" style="font-size:11px">Phân khúc:</span>
                        @foreach($segments as $segment)
                            <span class="status muted" style="font-size:10px;padding:2px 8px;background:var(--bg-panel-sub)">{{ $segment }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <button type="button" class="btn lime" onclick="openVoucherModal()">
                🎁 TẶNG VOUCHER
            </button>
            <button type="button" class="btn danger" onclick="openPasswordResetModal()">
                🔒 GỬI EMAIL ĐẶT LẠI MẬT KHẨU
            </button>
        </div>
    </div>
</div>

{{-- Top 4 KPI Cards --}}
<div class="kpi-grid" style="margin-bottom:20px;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr))">
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">TỔNG CHI TIÊU HOÀN TẤT</span>
            <span class="kpi-icon">💰</span>
        </div>
        <div class="kpi-val lime" style="font-size:1.6rem">{{ number_format($customer360['completed_spend'] ?? 0, 0, ',', '.') }} ₫</div>
        <div class="kpi-footer">
            <span>Doanh thu ròng từ đơn giao thành công</span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">ĐƠN HOÀN TẤT / TỔNG ĐƠN</span>
            <span class="kpi-icon">📦</span>
        </div>
        <div class="kpi-val" style="font-size:1.6rem">
            {{ number_format($customer360['completed_orders'] ?? 0) }} <span style="font-size:1rem;color:var(--text-muted)">/ {{ number_format($customer360['total_orders'] ?? 0) }}</span>
        </div>
        <div class="kpi-footer">
            <span class="muted">Tỷ lệ hoàn tất: {{ ($customer360['total_orders'] ?? 0) > 0 ? round((($customer360['completed_orders'] ?? 0) / $customer360['total_orders']) * 100) : 0 }}%</span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">GIÁ TRỊ ĐƠN TB (AOV)</span>
            <span class="kpi-icon">📊</span>
        </div>
        <div class="kpi-val" style="font-size:1.6rem">{{ number_format($customer360['average_order_value'] ?? 0, 0, ',', '.') }} ₫</div>
        <div class="kpi-footer">
            <span>Trung bình trên mỗi đơn hoàn tất</span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-title">ĐIỂM LOYALTY & HẠNG</span>
            <span class="kpi-icon">👑</span>
        </div>
        <div class="kpi-val lime" style="font-size:1.6rem">
            {{ number_format($loyalty['loyalty_points'] ?? 0) }} <span style="font-size:0.9rem;color:var(--text-sub)">pts</span>
        </div>
        <div class="kpi-footer">
            <span>Hạng <b>{{ $loyalty['tier'] }}</b> (Tiến độ {{ $loyalty['progress_percent'] }}%)</span>
        </div>
    </div>
</div>

{{-- Behavioral Insights Strip --}}
<div class="panel" style="margin-bottom:20px;padding:16px 20px;background:var(--bg-panel-sub)">
    <div style="font:700 11px/1 'DM Mono',monospace;letter-spacing:.1em;color:var(--lime);text-transform:uppercase;margin-bottom:12px">
        ⚡ HÀNH VI & ĐẶC TÍNH KHÁCH HÀNG (CUSTOMER INTELLIGENCE)
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px">
        <div>
            <div class="muted" style="font-size:11px">THƯƠNG HIỆU YÊU THÍCH</div>
            <b style="font-size:13px;color:var(--text-main)">{{ $customer360['preferred_brand'] ?? 'Chưa ghi nhận' }}</b>
        </div>
        <div>
            <div class="muted" style="font-size:11px">SIZE GIÀY PHỔ BIẾN</div>
            <b style="font-size:13px;color:var(--lime)">{{ !empty($customer360['common_size']) ? 'Size '.$customer360['common_size'] : 'Chưa có dữ liệu' }}</b>
        </div>
        <div>
            <div class="muted" style="font-size:11px">LOẠI ĐINH THƯỜNG DÙNG</div>
            <b style="font-size:13px;color:var(--text-main)">{{ $customer360['common_stud_type'] ?? '—' }}</b>
        </div>
        <div>
            <div class="muted" style="font-size:11px">MẶT SÂN ƯA CHUỘNG</div>
            <b style="font-size:13px;color:var(--text-main)">{{ $customer360['common_ground_type'] ?? '—' }}</b>
        </div>
        <div>
            <div class="muted" style="font-size:11px">KÊNH THANH TOÁN CHÍNH</div>
            <b style="font-size:13px;color:var(--text-main)">{{ !empty($customer360['common_payment_method']) ? \App\Support\UiLabels::paymentMethod($customer360['common_payment_method']) : '—' }}</b>
        </div>
        <div>
            <div class="muted" style="font-size:11px">ĐÁNH GIÁ TRUNG BÌNH</div>
            <b style="font-size:13px;color:#ffb703">{{ !empty($customer360['approved_review_average']) ? $customer360['approved_review_average'].' ★' : 'Chưa đánh giá' }}</b>
        </div>
        <div>
            <div class="muted" style="font-size:11px">LẦN MUA GẦN NHẤT</div>
            <b style="font-size:13px;color:var(--text-main)">{{ !empty($customer360['last_purchase']) ? \Carbon\Carbon::parse($customer360['last_purchase'])->format('d/m/Y') : 'Chưa có' }}</b>
        </div>
    </div>
</div>

{{-- Tab Navigation Bar --}}
<div class="dash-nav-bar" id="c360TabsNav" style="margin-bottom:20px">
    <button class="dash-tab-btn active" data-tab="overview">
        <span>▦ Tổng quan 360°</span>
    </button>
    <button class="dash-tab-btn" data-tab="orders">
        <span>📦 Đơn hàng</span>
        <span class="tab-badge" style="background:var(--bg-panel-sub);color:var(--text-main)">{{ $orders->count() }}</span>
    </button>
    <button class="dash-tab-btn" data-tab="vouchers">
        <span>🎁 Voucher & Ưu đãi</span>
        @if($personalVouchers->count() > 0)
            <span class="tab-badge" style="background:var(--lime);color:#07110d">{{ $personalVouchers->count() }}</span>
        @endif
    </button>
    <button class="dash-tab-btn" data-tab="reviews">
        <span>★ Đánh giá</span>
        <span class="tab-badge" style="background:var(--bg-panel-sub);color:var(--text-main)">{{ $reviews->count() }}</span>
    </button>
    <button class="dash-tab-btn" data-tab="support">
        <span>🎧 Yêu cầu hỗ trợ</span>
        @if($supportTickets->count() > 0)
            <span class="tab-badge" style="background:var(--info);color:#07110d">{{ $supportTickets->count() }}</span>
        @endif
    </button>
    <button class="dash-tab-btn" data-tab="teams">
        <span>🛡 Đội bóng & Loyalty</span>
        <span class="tab-badge" style="background:var(--bg-panel-sub);color:var(--text-main)">{{ $teams->count() }}</span>
    </button>
</div>

{{-- TAB 1: TỔNG QUAN --}}
<div class="dash-tab-pane active" id="pane-overview">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        {{-- Recent Orders Quick Preview --}}
        <section class="panel">
            <div class="toolbar" style="margin-bottom:12px">
                <span class="toolbar-title">📦 Đơn hàng gần đây</span>
                <a href="javascript:void(0)" onclick="switchTab('orders')" class="btn small">Xem tất cả đơn ({{ $orders->count() }})</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>MÃ ĐƠN</th>
                            <th>NGÀY</th>
                            <th>TRẠNG THÁI</th>
                            <th>THANH TOÁN</th>
                            <th style="text-align:right">TỔNG</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders->take(5) as $order)
                            <tr>
                                <td>
                                    <a class="lime-link mono" href="{{ route('admin.orders.show', $order) }}">
                                        <b>#{{ $order->number }}</b>
                                    </a>
                                </td>
                                <td class="muted" style="font-size:11px">{{ $order->created_at?->format('d/m/Y') }}</td>
                                <td><span class="status {{ $order->status }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span></td>
                                <td><span class="status {{ $order->payment_status }}">{{ \App\Support\UiLabels::paymentStatus($order->payment_status) }}</span></td>
                                <td style="text-align:right"><b class="mono" style="color:var(--lime)">{{ number_format($order->total, 0, ',', '.') }} ₫</b></td>
                                <td><a class="btn small" href="{{ route('admin.orders.show', $order) }}">Chi tiết</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted" style="text-align:center;padding:18px">Khách hàng chưa có đơn hàng nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Account & Address Information --}}
        <div>
            <section class="panel" style="margin-bottom:20px">
                <div class="toolbar-title" style="margin-bottom:14px">👤 Thông tin tài khoản</div>
                <div style="font-size:12px;display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;justify-content:space-between">
                        <span class="muted">Họ tên:</span>
                        <b>{{ $customer->name }}</b>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span class="muted">Email:</span>
                        <span class="mono">{{ $customer->email }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span class="muted">Số điện thoại:</span>
                        <span class="mono">{{ $customer->phone ?? $orders->first()?->recipient_phone ?? 'Chưa cập nhật' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span class="muted">Giới tính:</span>
                        <span>{{ match($customer->gender) { 'male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', default => 'Chưa đặt' } }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span class="muted">Ngày sinh:</span>
                        <span>{{ $customer->birthday ? \Carbon\Carbon::parse($customer->birthday)->format('d/m/Y') : 'Chưa đặt' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span class="muted">Hạng thành viên:</span>
                        <span class="vip-tier-badge {{ $tierLower }}">{{ $loyalty['tier'] }}</span>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="toolbar-title" style="margin-bottom:14px">📍 Sổ địa chỉ ({{ $customer->addresses->count() }})</div>
                @forelse($customer->addresses as $addr)
                    <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:6px;padding:10px;margin-bottom:8px;font-size:12px">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                            <b>{{ $addr->label ?? 'Địa chỉ' }}</b>
                            @if($addr->is_default)
                                <span class="status completed" style="font-size:9px">Mặc định</span>
                            @endif
                        </div>
                        <div class="muted">{{ $addr->recipient_name }} · {{ $addr->phone }}</div>
                        <div style="color:var(--text-sub);margin-top:2px">{{ $addr->address_line }}, {{ $addr->ward_code }}, {{ $addr->province_code }}</div>
                    </div>
                @empty
                    <div class="muted" style="font-size:12px">Chưa lưu địa chỉ nào.</div>
                @endforelse
            </section>
        </div>
    </div>
</div>

{{-- TAB 2: ĐƠN HÀNG --}}
<div class="dash-tab-pane" id="pane-orders">
    <section class="panel">
        <div class="toolbar" style="margin-bottom:14px">
            <span class="toolbar-title">📦 Đơn hàng gần đây ({{ $orders->count() }} / {{ $customer360['total_orders'] ?? 0 }})</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>MÃ ĐƠN</th>
                        <th>NGÀY TẠO</th>
                        <th>TRẠNG THÁI</th>
                        <th>THANH TOÁN</th>
                        <th>VẬN CHUYỂN</th>
                        <th>CHI TIẾT SẢN PHẨM</th>
                        <th style="text-align:right">TỔNG TIỀN</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <a class="lime-link mono" href="{{ route('admin.orders.show', $order) }}">
                                    <b>#{{ $order->number }}</b>
                                </a>
                            </td>
                            <td class="muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td><span class="status {{ $order->status }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span></td>
                            <td>
                                <span class="status {{ $order->payment_status }}">{{ \App\Support\UiLabels::paymentStatus($order->payment_status) }}</span>
                                <div class="muted" style="font-size:0.75rem;margin-top:2px">{{ \App\Support\UiLabels::paymentMethod($order->payment_method) }}</div>
                            </td>
                            <td>
                                <span class="status {{ $order->shipping_status ?? 'muted' }}">{{ \App\Support\UiLabels::ghnStatus($order->shipping_status) }}</span>
                                @if($order->ghn_order_code)
                                    <div class="muted mono" style="font-size:0.75rem;margin-top:2px">GHN: {{ $order->ghn_order_code }}</div>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:0.85rem">
                                    <b>{{ $order->items->count() }} sản phẩm:</b>
                                    <ul style="margin:4px 0 0 16px;padding:0;color:var(--text-muted)">
                                        @foreach($order->items as $item)
                                            <li>{{ $item->product_name }} ({{ $item->color ?? '—' }} / {{ $item->size ?? '—' }}) × {{ $item->quantity }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </td>
                            <td style="text-align:right">
                                <b class="mono" style="color:var(--lime);font-size:1rem">{{ number_format($order->total, 0, ',', '.') }} ₫</b>
                            </td>
                            <td>
                                <a class="btn small" href="{{ route('admin.orders.show', $order) }}">CHI TIẾT</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted" style="text-align:center;padding:24px">Khách hàng chưa có đơn hàng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- TAB 3: VOUCHER & ƯU ĐÃI CÁ NHÂN --}}
<div class="dash-tab-pane" id="pane-vouchers">
    <section class="panel">
        <div class="toolbar" style="margin-bottom:16px">
            <div>
                <span class="toolbar-title">🎁 VOUCHER CÁ NHÂN DÀNH RIÊNG CHO KHÁCH HÀNG</span>
                <div class="muted">Chỉ khách hàng này mới có quyền áp dụng mã khi thanh toán</div>
            </div>
            <button type="button" class="btn lime" onclick="openVoucherModal()">+ CẤP MÃ VOUCHER MỚI</button>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>MÃ CODE</th>
                        <th>LOẠI GIẢM GIÁ</th>
                        <th>GIÁ TRỊ</th>
                        <th>ĐIỀU KIỆN ÁP DỤNG</th>
                        <th>THỜI HẠN</th>
                        <th>LƯỢT DÙNG</th>
                        <th>TRẠNG THÁI</th>
                        <th>GHI CHÚ ADMIN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($personalVouchers as $voucher)
                        <tr>
                            <td>
                                <span class="mono" style="background:#132a1e;color:var(--lime);font-weight:700;padding:4px 8px;border-radius:4px;border:1px dashed var(--lime)">
                                    {{ $voucher->code }}
                                </span>
                            </td>
                            <td>
                                <span class="status muted">{{ $voucher->type === 'percent' ? 'Phần trăm (%)' : 'Cố định (VNĐ)' }}</span>
                            </td>
                            <td>
                                <b style="color:var(--lime);font-size:14px">
                                    {{ $voucher->type === 'percent' ? $voucher->value.'%' : number_format($voucher->value).' ₫' }}
                                </b>
                            </td>
                            <td style="font-size:12px">
                                @if($voucher->minimum_order_value)
                                    <div>Đơn từ: {{ number_format($voucher->minimum_order_value) }} ₫</div>
                                @endif
                                @if($voucher->max_discount)
                                    <div>Giảm tối đa: {{ number_format($voucher->max_discount) }} ₫</div>
                                @endif
                                @if(!$voucher->minimum_order_value && !$voucher->max_discount)
                                    <span class="muted">Không giới hạn</span>
                                @endif
                            </td>
                            <td class="muted" style="font-size:12px">
                                @if($voucher->expires_at)
                                    Hết hạn: {{ \Carbon\Carbon::parse($voucher->expires_at)->format('d/m/Y H:i') }}
                                    @if(\Carbon\Carbon::parse($voucher->expires_at)->isPast())
                                        <span class="status cancelled" style="font-size:9px">Đã hết hạn</span>
                                    @endif
                                @else
                                    <span>Vô thời hạn</span>
                                @endif
                            </td>
                            <td class="mono" style="font-size:12px">
                                {{ $voucher->used_count ?? 0 }} / {{ $voucher->usage_limit ?? '∞' }}
                            </td>
                            <td>
                                @if($voucher->is_active)
                                    <span class="status completed">Khả dụng</span>
                                @else
                                    <span class="status cancelled">Vô hiệu</span>
                                @endif
                            </td>
                            <td class="muted" style="font-size:11px">{{ $voucher->admin_note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted" style="text-align:center;padding:32px">
                                Khách hàng chưa có mã ưu đãi cá nhân nào. Bấm nút <b>"+ CẤP MÃ VOUCHER MỚI"</b> để tặng ưu đãi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- TAB 4: ĐÁNH GIÁ SẢN PHẨM --}}
<div class="dash-tab-pane" id="pane-reviews">
    <section class="panel">
        <div class="toolbar" style="margin-bottom:14px">
            <span class="toolbar-title">★ Đánh giá gần đây ({{ $reviews->count() }})</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>SẢN PHẨM</th>
                        <th>ĐÁNH GIÁ</th>
                        <th>NỘI DUNG</th>
                        <th>PHẢN HỒI FIELDCRAFT</th>
                        <th>TRẠNG THÁI</th>
                        <th>NGÀY</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        @php
                            $reviewStatusMap = [
                                'pending' => 'Chờ duyệt',
                                'approved' => 'Đã duyệt',
                                'rejected' => 'Từ chối',
                                'hidden' => 'Đã ẩn',
                            ];
                        @endphp
                        <tr>
                            <td><b>{{ $review->product?->name ?? 'Sản phẩm không tồn tại' }}</b></td>
                            <td style="color:#ffb703;white-space:nowrap">{{ str_repeat('★', (int) $review->rating) }}</td>
                            <td>{{ $review->comment }}</td>
                            <td>
                                @if($review->admin_reply)
                                    <div style="font-size:0.85rem;color:var(--lime)">{{ $review->admin_reply }}</div>
                                @else
                                    <span class="muted" style="font-size:0.8rem">Chưa phản hồi</span>
                                @endif
                            </td>
                            <td>
                                <span class="status {{ $review->status === 'approved' ? 'completed' : ($review->status === 'rejected' ? 'cancelled' : 'pending') }}">
                                    {{ $reviewStatusMap[$review->status] ?? ($review->status ? ucfirst($review->status) : '—') }}
                                </span>
                            </td>
                            <td class="muted" style="font-size:0.82rem">{{ $review->created_at?->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted" style="text-align:center;padding:24px">Khách hàng chưa gửi đánh giá nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- TAB 5: YÊU CẦU HỖ TRỢ --}}
<div class="dash-tab-pane" id="pane-support">
    <section class="panel">
        <div class="toolbar" style="margin-bottom:14px">
            <div>
                <span class="toolbar-title">🎧 YÊU CẦU HỖ TRỢ KHÁCH HÀNG (SUPPORT TICKETS)</span>
                <div class="muted">Toàn bộ thắc mắc, khiếu nại hoặc yêu cầu xử lý từ khách hàng</div>
            </div>
            <a href="{{ route('admin.dashboard', ['tab' => 'support']) }}#support" class="btn small lime">Mở Support Hub</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>MÃ TICKET</th>
                        <th>TIÊU ĐỀ</th>
                        <th>DANH MỤC</th>
                        <th>ĐƠN HÀNG</th>
                        <th>TIN NHẮN</th>
                        <th>CẬP NHẬT CUỐI</th>
                        <th>TRẠNG THÁI</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supportTickets as $ticket)
                        <tr>
                            <td><b class="mono">#{{ $ticket->id }}</b></td>
                            <td><b>{{ $ticket->subject }}</b></td>
                            <td>
                                <span class="status muted">{{ match($ticket->category) {
                                    'order' => 'Đơn hàng',
                                    'payment' => 'Thanh toán',
                                    'shipping' => 'Vận chuyển',
                                    'product' => 'Sản phẩm',
                                    'refund' => 'Hoàn tiền',
                                    'account' => 'Tài khoản',
                                    default => 'Khác'
                                } }}</span>
                            </td>
                            <td>
                                @if($ticket->order)
                                    <a class="lime-link mono" href="{{ route('admin.orders.show', $ticket->order) }}">#{{ $ticket->order->number }}</a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td><span class="mono">{{ $ticket->messages_count ?? $ticket->messages->count() }} tin</span></td>
                            <td class="muted" style="font-size:12px">{{ $ticket->last_message_at?->diffForHumans() ?? $ticket->updated_at->diffForHumans() }}</td>
                            <td>
                                <span class="status {{ $ticket->status === 'resolved' || $ticket->status === 'closed' ? 'completed' : 'pending' }}">
                                    {{ match($ticket->status) {
                                        'open' => 'Mới',
                                        'in_progress' => 'Đang xử lý',
                                        'resolved' => 'Đã giải quyết',
                                        'closed' => 'Đã đóng',
                                        default => $ticket->status
                                    } }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.dashboard', ['tab' => 'support']) }}#support" class="btn small">Xem & Phản hồi</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted" style="text-align:center;padding:24px">Khách hàng chưa có yêu cầu hỗ trợ nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- TAB 6: LOYALTY & ĐỘI BÓNG --}}
<div class="dash-tab-pane" id="pane-teams">
    {{-- Loyalty Tier Progress Section --}}
    <section class="panel" style="margin-bottom:22px">
        <div class="toolbar" style="margin-bottom:14px">
            <span class="toolbar-title">👑 TIẾN TRÌNH HẠNG THÀNH VIÊN</span>
        </div>
        <div style="background:var(--bg-panel-sub);padding:20px;border-radius:8px;border:1px solid var(--border-panel)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <div>
                    <span class="vip-tier-badge {{ $tierLower }}" style="font-size:12px;padding:4px 10px">{{ $loyalty['tier'] }}</span>
                    <span class="muted" style="font-size:12px;margin-left:10px">Điểm tích lũy: <b style="color:var(--lime)">{{ number_format($loyalty['loyalty_points'] ?? 0) }} điểm</b></span>
                </div>
                <div class="mono" style="color:var(--lime);font-weight:700">
                    {{ $loyalty['progress_percent'] }}% tiến độ
                </div>
            </div>
            <div style="height:10px;background:#050c08;border-radius:5px;overflow:hidden;border:1px solid var(--border-panel);margin-bottom:8px">
                <div style="height:100%;width:{{ $loyalty['progress_percent'] }}%;background:var(--lime)"></div>
            </div>
            <div class="muted" style="font-size:12px">
                @if(!empty($loyalty['next_threshold']))
                    Chi tiêu hiện tại: <b>{{ number_format($customer360['completed_spend'] ?? 0) }} ₫</b> / Mốc tiếp theo: <b>{{ number_format($loyalty['next_threshold']) }} ₫</b> (còn thiếu {{ number_format(max(0, $loyalty['next_threshold'] - ($customer360['completed_spend'] ?? 0))) }} ₫ để lên hạng).
                @else
                    Đã đạt thứ hạng cao nhất trong hệ sinh thái Fieldcraft!
                @endif
            </div>
        </div>
    </section>

    {{-- Teams Profiles Section --}}
    <section class="panel">
        <div class="toolbar" style="margin-bottom:14px">
            <span class="toolbar-title">🛡 Hồ sơ đội bóng liên kết ({{ $teams->count() }})</span>
        </div>
        @forelse($teams as $team)
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px;margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:12px">
                    <div>
                        <b style="font-size:1.15rem;color:var(--lime)">{{ $team->team_name }}</b>
                        <div class="muted" style="font-size:0.85rem;margin-top:3px">
                            Đội trưởng: <b>{{ $team->captain ?? 'Chưa đặt' }}</b> · SĐT: {{ $team->phone ?? '—' }} · {{ $team->members->count() }} thành viên
                        </div>
                    </div>
                    <div>
                        <a class="btn small lime" href="{{ route('admin.teams.draft-reorder', $team) }}" target="_blank">TẠO ĐƠN NHÁP TỪ ĐỘI</a>
                    </div>
                </div>
                @if($team->members->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table" style="font-size:0.85rem;margin-top:10px">
                            <thead>
                                <tr>
                                    <th>TÊN CẦU THỦ</th>
                                    <th>TÊN IN ÁO</th>
                                    <th>SỐ ÁO</th>
                                    <th>SIZE ÁO</th>
                                    <th>GHI CHÚ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($team->members as $member)
                                    <tr>
                                        <td><b>{{ $member->player_name }}</b></td>
                                        <td>{{ $member->shirt_name ?? '—' }}</td>
                                        <td><span class="status mono" style="background:#132a1e;color:var(--lime)">#{{ $member->shirt_number ?? '—' }}</span></td>
                                        <td>{{ $member->shirt_size ?? '—' }}</td>
                                        <td class="muted">{{ $member->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="muted" style="margin-top:8px;font-size:0.85rem">Chưa có thành viên trong danh sách đội.</p>
                @endif
            </div>
        @empty
            <p class="muted" style="text-align:center;padding:24px">Khách hàng chưa đăng ký hồ sơ đội bóng nào.</p>
        @endforelse
    </section>
</div>

{{-- MODAL: TẶNG VOUCHER CHO KHÁCH HÀNG --}}
<div id="voucherModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:12px;max-width:540px;width:100%;padding:24px;position:relative">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid var(--border-panel);padding-bottom:12px">
            <h3 style="font:700 18px/1 'Oswald',sans-serif;color:var(--text-main);margin:0">
                🎁 TẶNG VOUCHER CHO: {{ $customer->name }}
            </h3>
            <button type="button" onclick="closeVoucherModal()" style="background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer">✕</button>
        </div>

        <form id="voucherIssueForm" onsubmit="submitVoucherIssue(event)">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">LOẠI GIẢM GIÁ *</label>
                    <select name="type" required class="input" style="width:100%">
                        <option value="fixed">Cố định (VNĐ)</option>
                        <option value="percent">Phần trăm (%)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">GIÁ TRỊ *</label>
                    <input type="number" name="value" required min="1" placeholder="VD: 50000 hoặc 10" class="input" style="width:100%">
                </div>
            </div>

            <div style="margin-bottom:12px">
                <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">MÃ VOUCHER (ĐỂ TRỐNG ĐỂ HỆ THỐNG TỰ TẠO)</label>
                <input type="text" name="code" placeholder="VD: VIP-{{ strtoupper(str()->random(6)) }}" class="input" style="width:100%;text-transform:uppercase">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">ĐƠN HÀNG TỐI THIỂU (VNĐ)</label>
                    <input type="number" name="minimum_order_value" min="0" placeholder="0" class="input" style="width:100%">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">GIẢM TỐI ĐA (KHI %)</label>
                    <input type="number" name="max_discount" min="1" placeholder="Không giới hạn" class="input" style="width:100%">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">BẮT ĐẦU TỪ</label>
                    <input type="datetime-local" name="starts_at" class="input" style="width:100%">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">HẠN SỬ DỤNG</label>
                    <input type="datetime-local" name="expires_at" class="input" style="width:100%">
                </div>
            </div>

            <div style="margin-bottom:16px">
                <label class="form-label" style="font-size:11px;font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px">GHI CHÚ NỘI BỘ</label>
                <input type="text" name="admin_note" placeholder="VD: Tặng khách VIP dịp sinh nhật" class="input" style="width:100%">
            </div>

            <div id="voucherIssueError" class="errors" style="display:none;margin-bottom:12px"></div>

            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn" onclick="closeVoucherModal()">HỦY</button>
                <button type="submit" id="btnSubmitVoucher" class="btn lime">XÁC NHẬN CẤP VOUCHER</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: XÁC NHẬN GỬI EMAIL ĐẶT LẠI MẬT KHẨU --}}
<div id="passwordResetModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:12px;max-width:480px;width:100%;padding:24px;position:relative">
        <div style="margin-bottom:16px">
            <h3 style="font:700 18px/1 'Oswald',sans-serif;color:var(--danger);margin:0 0 8px">
                🔒 GỬI EMAIL ĐẶT LẠI MẬT KHẨU
            </h3>
            <p style="font-size:13px;color:var(--text-sub);line-height:1.5;margin:0">
                Hệ thống sẽ gửi email chứa đường link đặt lại mật khẩu an toàn đến hòm thư:
                <br>
                <b style="color:var(--text-main)">{{ $customer->email }}</b>
            </p>
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:6px;padding:10px;margin-top:12px;font-size:12px;color:var(--text-muted)">
                🛡️ <b>Chính sách bảo mật Fieldcraft:</b> Mật khẩu khách hàng được băm một chiều. Ban quản trị không xem hoặc phát sinh mật khẩu dạng văn bản thô (plaintext).
            </div>
        </div>

        <form method="POST" action="{{ route('admin.customers.reset-password', $customer) }}">
            @csrf
            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn" onclick="closePasswordResetModal()">HỦY BỎ</button>
                <button type="submit" class="btn danger">XÁC NHẬN GỬI EMAIL</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('#c360TabsNav .dash-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabId);
        });
        document.querySelectorAll('.dash-tab-pane').forEach(pane => {
            pane.classList.toggle('active', pane.id === 'pane-' + tabId);
        });
    }

    document.querySelectorAll('#c360TabsNav .dash-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    function openVoucherModal() {
        document.getElementById('voucherModal').style.display = 'flex';
    }
    function closeVoucherModal() {
        document.getElementById('voucherModal').style.display = 'none';
        document.getElementById('voucherIssueError').style.display = 'none';
    }
    function openPasswordResetModal() {
        document.getElementById('passwordResetModal').style.display = 'flex';
    }
    function closePasswordResetModal() {
        document.getElementById('passwordResetModal').style.display = 'none';
    }

    async function submitVoucherIssue(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSubmitVoucher');
        const errBox = document.getElementById('voucherIssueError');
        errBox.style.display = 'none';
        errBox.textContent = '';
        btn.disabled = true;
        btn.textContent = 'Đang cấp voucher...';

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        if (!payload.code) delete payload.code;
        if (!payload.minimum_order_value) delete payload.minimum_order_value;
        if (!payload.max_discount) delete payload.max_discount;
        if (!payload.starts_at) delete payload.starts_at;
        if (!payload.expires_at) delete payload.expires_at;
        if (!payload.admin_note) delete payload.admin_note;

        try {
            const res = await fetch('{{ route('admin.loyalty.vouchers.store', $customer) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok) {
                let msg = data.message || 'Lỗi cấp voucher.';
                if (data.errors) {
                    msg = Object.values(data.errors).flat().join('\n');
                }
                errBox.textContent = msg;
                errBox.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'XÁC NHẬN CẤP VOUCHER';
                return;
            }
            alert('✓ Đã cấp voucher thành công cho khách hàng!');
            window.location.reload();
        } catch (err) {
            errBox.textContent = 'Không thể kết nối máy chủ. Vui lòng thử lại.';
            errBox.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'XÁC NHẬN CẤP VOUCHER';
        }
    }
</script>
@endsection
