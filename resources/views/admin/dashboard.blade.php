@extends('layouts.admin')

@section('content')
@php
    $dash = $intelligence ?? app(\App\Services\AdminIntelligenceService::class)->dashboard();
    $revIntel = $revenueIntelligence ?? [
        '7' => app(\App\Services\AdminIntelligenceService::class)->revenue(7),
        '30' => app(\App\Services\AdminIntelligenceService::class)->revenue(30),
        '3m' => app(\App\Services\AdminIntelligenceService::class)->revenue('3m'),
        '12m' => app(\App\Services\AdminIntelligenceService::class)->revenue('12m'),
    ];
    $opsAlerts = $opsRadar ?? app(\App\Services\AdminIntelligenceService::class)->opsRadar();
    $financeData = $finance ?? app(\App\Services\AdminIntelligenceService::class)->finance();
    $inventoryProducts = app(\App\Services\AdminIntelligenceService::class)->inventoryMatrix();
    $restockItems = app(\App\Services\AdminIntelligenceService::class)->restockRadar();
    $performanceProducts = app(\App\Services\AdminIntelligenceService::class)->productPerformance();
    $teamsList = \App\Models\TeamProfile::with(['members', 'owner'])->latest()->get();
    $customizationJobs = \App\Models\CustomizationJob::with(['order.user', 'orderItem'])->latest()->take(25)->get();

    // Chart initial setup for default 30 days
    $defaultRev = $revIntel['30'] ?? reset($revIntel);
    $initialPeriods = $defaultRev['periods'] ?? [];
    $maxRevenue = max(array_column($initialPeriods, 'completed_revenue') ?: [1]);
@endphp

<style>
    /* ── Dashboard Navigation Tabs ── */
    .dash-nav-bar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        margin-bottom: 24px;
        border-bottom: 1px solid var(--border-panel);
    }
    .dash-tab-btn {
        background: transparent;
        border: 1px solid transparent;
        color: var(--text-muted);
        padding: 10px 18px;
        border-radius: 6px 6px 0 0;
        font: 700 12px/1 'Oswald', sans-serif;
        letter-spacing: .06em;
        text-transform: uppercase;
        cursor: pointer;
        transition: all .15s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    .dash-tab-btn:hover {
        color: var(--text-main);
        background: var(--bg-panel-sub);
    }
    .dash-tab-btn.active {
        background: var(--bg-panel);
        border-color: var(--border-panel);
        border-bottom-color: var(--bg-panel);
        color: var(--lime);
        box-shadow: 0 -2px 0 var(--lime) inset;
    }
    .tab-badge {
        font: 700 9px/1 'DM Mono', monospace;
        padding: 2px 5px;
        border-radius: 10px;
        background: var(--border-panel);
        color: var(--text-sub);
    }
    .dash-tab-btn.active .tab-badge {
        background: var(--lime);
        color: #07110d;
    }

    .dash-tab-pane {
        display: none;
    }
    .dash-tab-pane.active {
        display: block;
        animation: fadeIn .2s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

    /* ── KPI Grid ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .kpi-card {
        background: var(--bg-panel);
        border: 1px solid var(--border-panel);
        border-radius: 10px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        transition: transform .15s ease, border-color .15s ease;
    }
    .kpi-card:hover {
        border-color: var(--border-sub);
        transform: translateY(-2px);
    }
    .kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    .kpi-title {
        font: 700 11px/1 'DM Mono', monospace;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--text-muted);
    }
    .kpi-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: var(--bg-panel-sub);
        border: 1px solid var(--border-panel);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: var(--lime);
    }
    .kpi-val {
        font: 700 28px/1 'Oswald', sans-serif;
        color: var(--text-main);
        letter-spacing: .02em;
        margin-bottom: 8px;
    }
    .kpi-val.lime { color: var(--lime); }
    .kpi-footer {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: var(--text-muted);
    }
    .trend-pill {
        font: 700 10px/1 'DM Mono', monospace;
        padding: 2px 6px;
        border-radius: 3px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .trend-up {
        background: var(--success-bg);
        color: var(--lime);
        border: 1px solid var(--success-border);
    }
    .trend-down {
        background: var(--danger-bg);
        color: var(--danger);
        border: 1px solid var(--danger-border);
    }

    /* ── Ops Radar Banner ── */
    .radar-panel {
        background: var(--bg-panel);
        border: 1px solid var(--border-panel);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .alert-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .radar-alert-card {
        padding: 14px 16px;
        border-radius: 8px;
        background: var(--bg-panel-sub);
        border: 1px solid var(--border-panel);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 10px;
        text-decoration: none;
        transition: all .15s ease;
    }
    .radar-alert-card:hover {
        transform: translateY(-2px);
    }
    .radar-alert-card.high {
        border-color: var(--danger-border);
        background: linear-gradient(135deg, var(--danger-bg), var(--bg-panel-sub));
    }
    .radar-alert-card.high:hover {
        border-color: var(--danger);
        box-shadow: 0 0 12px rgba(255, 107, 74, 0.2);
    }
    .radar-alert-card.medium {
        border-color: var(--warning-border);
        background: linear-gradient(135deg, var(--warning-bg), var(--bg-panel-sub));
    }
    .radar-alert-card.medium:hover {
        border-color: var(--warning);
        box-shadow: 0 0 12px rgba(251, 191, 36, 0.2);
    }
    .alert-badge {
        font: 700 9px/1 'DM Mono', monospace;
        letter-spacing: .08em;
        text-transform: uppercase;
        padding: 3px 6px;
        border-radius: 3px;
        width: fit-content;
    }
    .alert-badge.high { background: var(--danger); color: #07110d; }
    .alert-badge.medium { background: var(--warning); color: #07110d; }

    /* ── Chart Container ── */
    .chart-container {
        background: var(--bg-panel-sub);
        border: 1px solid var(--border-panel);
        border-radius: 8px;
        padding: 20px;
        margin-top: 16px;
    }
    .chart-bars-wrap {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        height: 200px;
        padding-top: 20px;
        overflow-x: auto;
    }
    .chart-bar-col {
        flex: 1;
        min-width: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        height: 100%;
        justify-content: flex-end;
        position: relative;
    }
    .chart-bar-rect {
        width: 100%;
        background: var(--lime);
        border-radius: 3px 3px 0 0;
        transition: height .3s ease, background-color .15s ease;
        min-height: 4px;
        cursor: pointer;
    }
    .chart-bar-rect:hover {
        background: #ffffff;
        box-shadow: 0 0 10px var(--lime);
    }
    .chart-x-label {
        font: 700 9px/1 'DM Mono', monospace;
        color: var(--text-muted);
        text-align: center;
        white-space: nowrap;
    }

    /* ── Matrix Styles ── */
    .matrix-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 4px;
    }
    .matrix-table th {
        background: var(--bg-panel-sub);
        border: 1px solid var(--border-panel);
        padding: 10px;
        text-align: center;
        font: 700 11px/1 'DM Mono', monospace;
        color: var(--text-sub);
        border-radius: 4px;
    }
    .matrix-table th.important-size {
        background: rgba(202, 255, 57, 0.08);
        border-color: var(--lime);
        color: var(--lime);
    }
    .matrix-cell {
        background: var(--bg-panel-sub);
        border: 1px solid var(--border-panel);
        border-radius: 6px;
        padding: 10px 8px;
        text-align: center;
        cursor: pointer;
        transition: all .15s ease;
    }
    .matrix-cell:hover {
        border-color: var(--lime);
        transform: scale(1.03);
    }
    .matrix-cell.cell-out {
        background: #141715;
        border-color: #202422;
        color: var(--text-muted);
    }
    .matrix-cell.cell-critical {
        background: var(--danger-bg);
        border-color: var(--danger-border);
        color: var(--danger);
    }
    .matrix-cell.cell-low {
        background: var(--warning-bg);
        border-color: var(--warning-border);
        color: var(--warning);
    }
    .matrix-cell.cell-normal {
        background: var(--success-bg);
        border-color: var(--success-border);
        color: var(--lime);
    }
    .cell-stock-num {
        font: 700 14px/1 'DM Mono', monospace;
    }
    .cell-state-label {
        font: 700 8px/1 'DM Mono', monospace;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-top: 3px;
        display: block;
    }

    /* ── Workflow Stepper ── */
    .workflow-stepper {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding: 16px 0;
    }
    .wf-step {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--bg-panel-sub);
        border: 1px solid var(--border-panel);
        padding: 8px 14px;
        border-radius: 6px;
        font: 700 11px/1 'Oswald', sans-serif;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-muted);
        white-space: nowrap;
    }
    .wf-step.active {
        border-color: var(--lime);
        color: var(--lime);
        background: var(--success-bg);
        box-shadow: 0 0 10px rgba(202, 255, 57, 0.2);
    }
    .wf-arrow { color: var(--border-sub); font-size: 14px; }

    /* ── Responsive ── */
    @media (max-width: 1200px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .kpi-grid { grid-template-columns: 1fr; }
        .alert-cards-grid { grid-template-columns: 1fr; }
    }
</style>

{{-- Header Row --}}
<div class="crumb">FIELDCRAFT / CONTROL CENTER PRO MAX</div>
<div class="topline">
    <div>
        <h1>Bảng điều khiển</h1>
        <div class="muted">Hệ thống giám sát thương mại bóng đá thời gian thực</div>
    </div>
    <div class="actions">
        <a href="{{ route('admin.orders.index') }}" class="btn lime">📦 Đơn hàng mới</a>
        <a href="{{ route('admin.products.create') }}" class="btn">+ Thêm sản phẩm</a>
    </div>
</div>

{{-- Top Control Tabs --}}
<div class="dash-nav-bar" id="dashTabsNav">
    <button class="dash-tab-btn active" data-tab="overview">
        <span>▦ Tổng quan & Vận hành</span>
        @if($opsAlerts->count() > 0)
            <span class="tab-badge" style="background:var(--warning);color:#07110d">{{ $opsAlerts->count() }} cảnh báo</span>
        @endif
    </button>
    <button class="dash-tab-btn" data-tab="revenue">
        <span>📈 Doanh thu & Tài chính</span>
    </button>
    <button class="dash-tab-btn" data-tab="inventory">
        <span>👟 Ma trận kho & Nhập hàng</span>
        @if($dash['low_stock_variant_count'] > 0)
            <span class="tab-badge" style="background:var(--danger);color:#fff">{{ $dash['low_stock_variant_count'] }}</span>
        @endif
    </button>
    <button class="dash-tab-btn" data-tab="performance">
        <span>⚡ Hiệu suất sản phẩm</span>
    </button>
    <button class="dash-tab-btn" data-tab="teams">
        <span>🛡 Đội bóng & In ấn</span>
    </button>
</div>

{{-- ========================================================================= --}}
{{-- TAB 1: TỔNG QUAN & SMART OPS RADAR                                        --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane active" id="tab-overview">
    {{-- 1. 8 KPI CARDS --}}
    <div class="kpi-grid">
        {{-- Card 1: Tổng giá trị đơn hàng --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Tổng giá trị đơn hàng</span>
                <span class="kpi-icon">💎</span>
            </div>
            <div class="kpi-val lime">{{ number_format($dash['total_order_value']) }} ₫</div>
            <div class="kpi-footer">
                <span>Toàn bộ đơn hàng trừ đơn hủy</span>
            </div>
        </div>

        {{-- Card 2: Doanh thu hoàn tất --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Doanh thu hoàn tất</span>
                <span class="kpi-icon">✓</span>
            </div>
            <div class="kpi-val">{{ number_format($dash['completed_revenue']) }} ₫</div>
            <div class="kpi-footer">
                <span class="muted">{{ $dash['completed_orders'] }} đơn giao thành công</span>
            </div>
        </div>

        {{-- Card 3: Doanh thu tháng này --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Doanh thu tháng này</span>
                <span class="kpi-icon">📅</span>
            </div>
            <div class="kpi-val lime">{{ number_format($dash['revenue_this_month']) }} ₫</div>
            <div class="kpi-footer">
                @php $monthTrend = $dash['trends']['month_revenue']['change_percent'] ?? 0; @endphp
                <span class="trend-pill {{ $monthTrend >= 0 ? 'trend-up' : 'trend-down' }}">
                    {{ $monthTrend >= 0 ? '↑' : '↓' }} {{ abs($monthTrend) }}%
                </span>
                <span>so với tháng trước</span>
            </div>
        </div>

        {{-- Card 4: Đơn hôm nay --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Đơn hôm nay</span>
                <span class="kpi-icon">⚡</span>
            </div>
            <div class="kpi-val">{{ $dash['order_count_today'] }}</div>
            <div class="kpi-footer">
                @php $todayTrend = $dash['trends']['today_revenue']['change_percent'] ?? 0; @endphp
                <span class="trend-pill {{ $todayTrend >= 0 ? 'trend-up' : 'trend-down' }}">
                    {{ $todayTrend >= 0 ? '↑' : '↓' }} {{ abs($todayTrend) }}%
                </span>
                <span>Doanh thu: {{ number_format($dash['revenue_today']) }} ₫</span>
            </div>
        </div>

        {{-- Card 5: Giá trị đơn trung bình (AOV) --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Giá trị đơn trung bình</span>
                <span class="kpi-icon">⚖</span>
            </div>
            <div class="kpi-val">{{ number_format($dash['average_completed_order_value']) }} ₫</div>
            <div class="kpi-footer">
                <span>Tính trên các đơn hoàn tất</span>
            </div>
        </div>

        {{-- Card 6: Khách hàng --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Khách hàng</span>
                <span class="kpi-icon">👥</span>
            </div>
            <div class="kpi-val">{{ number_format($dash['customer_count']) }}</div>
            <div class="kpi-footer">
                <a href="{{ route('admin.customers.index') }}" class="lime-link">Xem danh sách khách hàng →</a>
            </div>
        </div>

        {{-- Card 7: Đang giao hàng --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Đang giao hàng</span>
                <span class="kpi-icon">🚚</span>
            </div>
            <div class="kpi-val" style="color:var(--info)">{{ $dash['shipping_order_count'] }}</div>
            <div class="kpi-footer">
                <a href="{{ route('admin.orders.index', ['status' => 'shipping']) }}" class="lime-link">Theo dõi vận chuyển GHN →</a>
            </div>
        </div>

        {{-- Card 8: Đánh giá chờ duyệt --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Đánh giá chờ duyệt</span>
                <span class="kpi-icon">★</span>
            </div>
            <div class="kpi-val" style="color:var(--warning)">{{ $dash['pending_review_count'] }}</div>
            <div class="kpi-footer">
                <a href="{{ route('admin.reviews.index') }}" class="lime-link">Kiểm duyệt đánh giá →</a>
            </div>
        </div>
    </div>

    {{-- 3. SMART OPS RADAR ("Trung tâm cảnh báo") --}}
    <section class="radar-panel">
        <div class="toolbar" style="margin-bottom:0">
            <div>
                <div class="toolbar-title">
                    <span>🚨 TRUNG TÂM CẢNH BÁO VẬN HÀNH (SMART OPS RADAR)</span>
                </div>
                <div class="muted">Tự động quét và phát hiện các rủi ro vận đơn, thanh toán, kho và trải nghiệm khách hàng</div>
            </div>
            <div class="actions">
                <span class="status {{ $opsAlerts->isEmpty() ? 'completed' : 'pending' }}">
                    {{ $opsAlerts->isEmpty() ? 'HỆ THỐNG TỐI ƯU' : $opsAlerts->count().' VẤN ĐỀ CẦN XỬ LÝ' }}
                </span>
            </div>
        </div>

        @if($opsAlerts->isNotEmpty())
            @php
                $alertTypeLabels = [
                    'paid_without_waybill' => 'Chưa có vận đơn',
                    'shipping_stuck' => 'Đơn giao bị treo',
                    'ghn_delivered_not_completed' => 'Chưa hoàn tất đơn',
                    'repeated_payment_failures' => 'Lỗi thanh toán nhiều lần',
                    'low_stock_variant' => 'Tồn kho thấp',
                    'important_size_low_stock' => 'Size chủ lực sắp hết',
                    'low_rating_without_reply' => 'Đánh giá thấp chưa phản hồi',
                    'pending_confirmation' => 'Chờ xác nhận lâu',
                ];
            @endphp
            <div class="alert-cards-grid">
                @foreach($opsAlerts as $alert)
                    @php
                        $isHigh = in_array($alert['severity'], ['high', 'critical'], true);
                        $targetUrl = route($alert['target_route'], $alert['entity_id']);
                    @endphp
                    <a href="{{ $targetUrl }}" class="radar-alert-card {{ $isHigh ? 'high' : 'medium' }}">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                            <b style="color:var(--text-main);font-size:13px">{{ $alert['title'] }}</b>
                            <span class="alert-badge {{ $isHigh ? 'high' : 'medium' }}">
                                {{ $isHigh ? 'KHẨN CẤP' : 'CẢNH BÁO' }}
                            </span>
                        </div>
                        <div class="muted" style="font-size:12px;line-height:1.4">
                            {{ $alert['description'] }}
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
                            <span class="mono muted" style="font-size:10px">Phân loại: {{ $alertTypeLabels[$alert['type']] ?? ($alert['type'] ? ucfirst(str_replace('_', ' ', $alert['type'])) : 'Khác') }}</span>
                            <span class="lime-link" style="font-size:11px">XỬ LÝ NGAY →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div style="margin-top:16px;background:var(--success-bg);border:1px solid var(--success-border);color:var(--lime);padding:16px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:10px">
                <span style="font-size:20px">✓</span>
                <span>Toàn bộ vận đơn, thanh toán và tồn kho đang vận hành trơn tru. Không có cảnh báo tồn đọng.</span>
            </div>
        @endif
    </section>

    {{-- Recent Orders & Status Distribution --}}
    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;">
        {{-- Recent Orders --}}
        <section class="panel" style="margin-bottom:0">
            <div class="toolbar">
                <span class="toolbar-title">Đơn hàng gần đây</span>
                <a class="btn small" href="{{ route('admin.orders.index') }}">Xem tất cả</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Trạng thái</th>
                            <th>Tổng tiền</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td>
                                    <a class="lime-link mono" href="{{ route('admin.orders.show', $order) }}">#{{ $order->number }}</a>
                                    <div class="muted mono" style="font-size:10px">{{ $order->created_at?->format('d/m H:i') }}</div>
                                </td>
                                <td>
                                    <b style="color:var(--text-main)">{{ $order->user?->name ?? $order->recipient_name }}</b>
                                    <div class="muted mono" style="font-size:10px">{{ $order->recipient_phone }}</div>
                                </td>
                                <td>
                                    <span class="status {{ $order->status }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span>
                                </td>
                                <td>
                                    <b class="mono">{{ number_format($order->total) }} ₫</b>
                                </td>
                                <td>
                                    <a class="btn small" href="{{ route('admin.orders.show', $order) }}">Chi tiết</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="muted">Chưa có đơn hàng nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Status Breakdown --}}
        <section class="panel" style="margin-bottom:0">
            <div class="toolbar">
                <span class="toolbar-title">Phân bổ trạng thái</span>
                <span class="mono muted">{{ $ordersCount }} tổng đơn</span>
            </div>
            @forelse($statusCounts as $status => $count)
                @php $pct = $ordersCount > 0 ? round(($count / $ordersCount) * 100, 1) : 0; @endphp
                <div style="margin:14px 0">
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
                        <span>{{ \App\Support\UiLabels::orderStatus($status) }}</span>
                        <b class="mono">{{ $count }} ({{ $pct }}%)</b>
                    </div>
                    <div style="height:6px;background:var(--bg-panel-sub);border-radius:10px;overflow:hidden">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $status === 'completed' ? 'var(--lime)' : ($status === 'cancelled' ? 'var(--danger)' : 'var(--info)') }}"></div>
                    </div>
                </div>
            @empty
                <p class="muted">Chưa có dữ liệu trạng thái.</p>
            @endforelse
        </section>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- TAB 2: REVENUE INTELLIGENCE & FINANCE CENTER                              --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-revenue">
    {{-- 2. REVENUE INTELLIGENCE --}}
    <section class="panel">
        <div class="toolbar">
            <div>
                <div class="toolbar-title">📈 THÔNG MINH DOANH THU (REVENUE INTELLIGENCE)</div>
                <div class="muted">Phân tích chu kỳ và phương thức thanh toán từ dữ liệu giao dịch hoàn tất thực tế</div>
            </div>
            <div class="actions">
                {{-- Range Selector --}}
                <div style="display:flex;gap:4px;background:var(--bg-panel-sub);border:1px solid var(--border-panel);padding:3px;border-radius:6px">
                    <button type="button" class="btn small rev-range-btn" data-range="7">7 NGÀY</button>
                    <button type="button" class="btn small rev-range-btn lime" data-range="30">30 NGÀY</button>
                    <button type="button" class="btn small rev-range-btn" data-range="3m">3 THÁNG</button>
                    <button type="button" class="btn small rev-range-btn" data-range="12m">12 THÁNG</button>
                </div>

                {{-- Metric Selector --}}
                <div style="display:flex;gap:4px;background:var(--bg-panel-sub);border:1px solid var(--border-panel);padding:3px;border-radius:6px">
                    <button type="button" class="btn small rev-metric-btn lime" data-metric="revenue">Doanh thu</button>
                    <button type="button" class="btn small rev-metric-btn" data-metric="orders">Đơn hàng</button>
                    <button type="button" class="btn small rev-metric-btn" data-metric="aov">Giá trị TB</button>
                </div>
            </div>
        </div>

        {{-- Interactive Chart Area --}}
        <div class="chart-container">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:14px">
                <span class="mono muted" style="font-size:11px" id="chartPeriodSummary">Tổng 30 ngày qua</span>
                <b class="mono" style="font-size:22px;color:var(--lime)" id="chartTotalDisplay">{{ number_format($defaultRev['totals']['completed_revenue'] ?? 0) }} ₫</b>
            </div>

            <div class="chart-bars-wrap" id="chartBarsContainer">
                @foreach($initialPeriods as $p)
                    @php
                        $val = $p['completed_revenue'];
                        $height = $maxRevenue > 0 ? max(4, (int)(($val / $maxRevenue) * 160)) : 4;
                    @endphp
                    <div class="chart-bar-col" title="{{ $p['period'] }}: {{ number_format($val) }} ₫ ({{ $p['order_count'] }} đơn)">
                        <div class="chart-bar-rect" style="height:{{ $height }}px"></div>
                        <span class="chart-x-label">{{ substr($p['period'], -5) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Payment Method Breakdown --}}
        <div style="margin-top:24px">
            <h3 style="font:700 13px/1 'Oswald',sans-serif;text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px">
                Phân bổ theo phương thức thanh toán
            </h3>
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:14px" id="paymentBreakdownCards">
                @php
                    $breakdown = $defaultRev['payment_breakdown'] ?? [];
                    $totalRevSum = max(1, array_sum(array_column($breakdown, 'amount')));
                @endphp
                @foreach(['cod' => 'Thanh toán khi nhận (COD)', 'momo' => 'Ví điện tử MoMo', 'bank_qr_payos' => 'Chuyển khoản Bank QR / payOS'] as $mKey => $mName)
                    @php
                        $mData = $breakdown[$mKey] ?? ['amount' => 0, 'order_count' => 0];
                        $mPct = round(($mData['amount'] / $totalRevSum) * 100, 1);
                    @endphp
                    <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                        <div class="muted" style="font-size:11px;font-weight:700;margin-bottom:6px">{{ $mName }}</div>
                        <div class="mono" style="font-size:18px;font-weight:700;color:var(--text-main);margin-bottom:4px">
                            {{ number_format($mData['amount']) }} ₫
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:8px">
                            <span>{{ $mData['order_count'] }} đơn hoàn tất</span>
                            <span class="mono">{{ $mPct }}%</span>
                        </div>
                        <div style="height:4px;background:var(--bg-panel);border-radius:2px;overflow:hidden">
                            <div style="height:100%;width:{{ $mPct }}%;background:var(--lime)"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. FINANCE CENTER --}}
    <section class="panel" id="finance">
        <div class="toolbar">
            <span class="toolbar-title">💰 TRUNG TÂM TÀI CHÍNH & ĐỐI SOÁT</span>
            <span class="status completed">SỔ CÁI HOÀN TẤT</span>
        </div>

        <div class="kpi-grid" style="margin-bottom:20px">
            <div class="kpi-card">
                <span class="kpi-title">Doanh thu hoàn tất</span>
                <div class="kpi-val lime">{{ number_format($financeData['total_completed_revenue']) }} ₫</div>
                <span class="muted" style="font-size:11px">Giao dịch đã xác nhận thành công</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-title">Online Paid</span>
                <div class="kpi-val" style="color:var(--info)">{{ number_format($financeData['online_paid_amount']) }} ₫</div>
                <span class="muted" style="font-size:11px">Đã thu qua MoMo & VietQR</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-title">COD chờ thu tiền</span>
                <div class="kpi-val" style="color:var(--warning)">{{ number_format($financeData['cod_pending_collection']) }} ₫</div>
                <span class="muted" style="font-size:11px">Đang giao hoặc chờ GHN thu hộ</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-title">Đơn hủy / Hoàn tiền</span>
                <div class="kpi-val" style="color:var(--danger)">{{ number_format($financeData['cancelled_order_value']) }} ₫</div>
                <span class="muted" style="font-size:11px">Hoàn tiền: {{ number_format($financeData['refunded_amount']) }} ₫</span>
            </div>
        </div>

        {{-- Reconciliation Audit Table --}}
        <h3 style="font:700 13px/1 'Oswald',sans-serif;text-transform:uppercase;letter-spacing:.04em;margin:20px 0 12px">
            Nhật ký đối soát thanh toán gần đây
        </h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Đơn hàng</th>
                        <th>Cổng thanh toán</th>
                        <th>Trạng thái cổng</th>
                        <th>Trạng thái đơn</th>
                        <th>Số tiền</th>
                        <th>Đối soát</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($financeData['reconciliation'] as $p)
                        @php
                            $isMismatch = ($p['provider_status'] === 'paid' && $p['order_payment_status'] !== 'paid');
                        @endphp
                        <tr>
                            <td>
                                <a class="lime-link mono" href="{{ route('admin.orders.show', $p['order_id']) }}">
                                    #{{ $p['order_number'] ?? $p['order_id'] }}
                                </a>
                            </td>
                            <td>
                                <span class="mono" style="text-transform:uppercase">{{ $p['provider'] }}</span>
                            </td>
                            <td>
                                <span class="status {{ $p['provider_status'] }}">
                                    {{ \App\Support\UiLabels::paymentStatus($p['provider_status']) }}
                                </span>
                            </td>
                            <td>
                                <span class="status {{ $p['order_payment_status'] }}">
                                    {{ \App\Support\UiLabels::paymentStatus($p['order_payment_status']) }}
                                </span>
                            </td>
                            <td>
                                <b class="mono">{{ number_format($p['amount']) }} ₫</b>
                            </td>
                            <td>
                                @if($isMismatch)
                                    <span class="status rejected">LỆCH ĐỐI SOÁT</span>
                                @else
                                    <span class="status approved">✓ KHỚP</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Chưa có giao dịch đối soát.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 3: INVENTORY MATRIX & RESTOCK RADAR                                   --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-inventory">
    {{-- 5. FOOTWEAR STOCK MATRIX --}}
    <section class="panel" id="inventory">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">👟 MA TRẬN TỒN KHO GIÀY BÓNG ĐÁ (INVENTORY MATRIX)</span>
                <div class="muted">Phân bố tồn kho đa chiều theo Màu sắc & Cỡ giày (Size). Size 40/41 là size chủ lực được nhận diện ưu tiên.</div>
            </div>
            <a href="{{ route('admin.products.index') }}" class="btn lime">Quản lý kho</a>
        </div>

        @forelse($inventoryProducts as $prod)
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px;margin-bottom:20px">
                {{-- Product Specs Bar --}}
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;border-bottom:1px solid var(--border-panel);padding-bottom:12px">
                    <div>
                        <h3 style="font:700 18px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:4px">
                            {{ $prod['product'] }}
                        </h3>
                        <div class="muted mono" style="font-size:11px">Mã sản phẩm: FC-P{{ $prod['product_id'] }}</div>
                    </div>

                    {{-- Spec chips --}}
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        @php
                            $firstVariant = $prod['colors'][0]['variants'][0] ?? null;
                            $totalProdStock = collect($prod['colors'])->flatMap->variants->sum('stock');
                        @endphp
                        <span class="status muted">Đinh: <b>{{ $firstVariant['stud_type'] ?? 'TF/FG' }}</b></span>
                        <span class="status muted">Phom: <b>{{ $firstVariant['foot_shape'] ?? 'Vừa' }}</b></span>
                        <span class="status muted">Mặt sân: <b>{{ $firstVariant['surface_type'] ?? 'Cỏ nhân tạo' }}</b></span>
                        <span class="status completed">Tổng tồn: <b class="mono">{{ $totalProdStock }}</b> đôi</span>
                    </div>
                </div>

                {{-- Color x Size Matrix --}}
                @php
                    $allSizes = ['38', '39', '40', '41', '42', '43', '44'];
                @endphp
                <div class="table-responsive">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th style="width:140px;text-align:left">MÀU SẮC</th>
                                @foreach($allSizes as $sz)
                                    <th class="{{ in_array($sz, ['40', '41']) ? 'important-size' : '' }}">
                                        Size {{ $sz }}
                                        @if(in_array($sz, ['40', '41']))
                                            <div style="font-size:8px;color:var(--lime)">★ CHỦ LỰC</div>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prod['colors'] as $cGroup)
                                @php
                                    $variantsBySize = collect($cGroup['variants'])->keyBy('size');
                                @endphp
                                <tr>
                                    <td style="font-weight:700;color:var(--text-main);background:var(--bg-panel-sub);padding:10px;border-radius:4px;border:1px solid var(--border-panel)">
                                        {{ $cGroup['color'] }}
                                    </td>
                                    @foreach($allSizes as $sz)
                                        @php
                                            $v = $variantsBySize->get($sz);
                                            $stk = $v['stock'] ?? null;
                                            if (is_null($stk)) {
                                                $cellClass = 'cell-out';
                                                $lbl = 'K.CÓ';
                                            } elseif ($stk == 0) {
                                                $cellClass = 'cell-out';
                                                $lbl = 'HẾT HÀNG';
                                            } elseif ($stk <= 2) {
                                                $cellClass = 'cell-critical';
                                                $lbl = 'NGUY CẤP';
                                            } elseif ($v['low_stock'] ?? false) {
                                                $cellClass = 'cell-low';
                                                $lbl = 'SẮP HẾT';
                                            } else {
                                                $cellClass = 'cell-normal';
                                                $lbl = 'TỐT';
                                            }
                                        @endphp
                                        <td class="matrix-cell {{ $cellClass }}" onclick="window.location.href='{{ route('admin.products.edit', $prod['product_id']) }}'" title="Nhấp để sửa sản phẩm / biến thể">
                                            <div class="cell-stock-num">{{ is_null($stk) ? '—' : $stk }}</div>
                                            <span class="cell-state-label">{{ $lbl }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="muted">Chưa có dữ liệu ma trận kho.</p>
        @endforelse
    </section>

    {{-- 6. RESTOCK RADAR --}}
    <section class="panel" id="restock">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">⚡ RADAR NHẬP HÀNG (RESTOCK RADAR)</span>
                <div class="muted">Dự báo tốc độ bán (velocity) và cảnh báo sản phẩm cần bổ sung tồn kho ngay</div>
            </div>
            <div class="actions">
                <button type="button" class="btn small lime restock-filter-btn" data-filter="all">Tất cả</button>
                <button type="button" class="btn small restock-filter-btn" data-filter="restock_recommended">NÊN NHẬP THÊM</button>
                <button type="button" class="btn small restock-filter-btn" data-filter="important">Size 40/41</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" id="restockTable">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Phân loại</th>
                        <th>Tồn kho</th>
                        <th>Đã bán (30 ngày)</th>
                        <th>Tốc độ (đơn/ngày)</th>
                        <th>Khuyến nghị</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($restockItems as $r)
                        <tr data-rec="{{ $r['recommendation'] }}" data-size="{{ $r['size'] }}">
                            <td>
                                <b style="color:var(--text-main)">{{ $r['product'] }}</b>
                            </td>
                            <td>
                                <span class="mono">{{ $r['color'] }} / Size {{ $r['size'] }}</span>
                                @if(in_array($r['size'], ['40', '41']))
                                    <span class="status pending" style="font-size:8px;padding:2px 4px">CHỦ LỰC</span>
                                @endif
                            </td>
                            <td>
                                <b class="mono {{ $r['stock'] <= 2 ? 'lime-text' : '' }}" style="{{ $r['stock'] <= 2 ? 'color:var(--danger)' : '' }}">
                                    {{ $r['stock'] }}
                                </b>
                            </td>
                            <td>
                                <span class="mono">{{ $r['sold_last_30_days'] }} đôi</span>
                            </td>
                            <td>
                                <span class="mono">{{ $r['sales_velocity_per_day'] }}</span>
                            </td>
                            <td>
                                @if($r['recommendation'] === 'restock_recommended')
                                    <span class="status cancelled" style="background:var(--danger);color:#07110d;font-weight:800;animation:pulse-dot 2s infinite">
                                        ⚡ NÊN NHẬP THÊM
                                    </span>
                                @elseif($r['recommendation'] === 'watch')
                                    <span class="status pending">THEO DÕI</span>
                                @else
                                    <span class="status completed">ỔN ĐỊNH</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn small" href="{{ route('admin.products.index') }}">Nhập hàng</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Tồn kho đang ở mức an toàn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 4: PRODUCT PERFORMANCE                                                --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-performance">
    {{-- 7. PRODUCT PERFORMANCE --}}
    <section class="panel">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🏆 HIỆU SUẤT SẢN PHẨM (PRODUCT PERFORMANCE)</span>
                <div class="muted">Bảng xếp hạng năng lực sinh lời, số lượng bán ra và màu/size bán chạy nhất</div>
            </div>
            <a href="{{ route('admin.products.create') }}" class="btn lime">+ Thêm sản phẩm</a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đã bán</th>
                        <th>Doanh thu hoàn tất</th>
                        <th>Tồn kho</th>
                        <th>Đánh giá</th>
                        <th>Màu hot nhất</th>
                        <th>Size hot nhất</th>
                        <th>30 ngày gần đây</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($performanceProducts->sortByDesc('sold_quantity') as $perf)
                        <tr>
                            <td>
                                <b style="color:var(--text-main);font-size:13px">{{ $perf['product'] }}</b>
                                <div class="muted mono" style="font-size:10px">ID: {{ $perf['product_id'] }}</div>
                            </td>
                            <td>
                                <b class="mono" style="font-size:14px;color:var(--lime)">{{ number_format($perf['sold_quantity']) }}</b> đôi
                            </td>
                            <td>
                                <b class="mono">{{ number_format($perf['completed_revenue']) }} ₫</b>
                            </td>
                            <td>
                                <span class="mono">{{ number_format($perf['total_stock']) }}</span>
                            </td>
                            <td>
                                @if($perf['average_approved_rating'])
                                    <span style="color:var(--warning)">★ {{ $perf['average_approved_rating'] }}</span>
                                @else
                                    <span class="muted">Chưa có</span>
                                @endif
                            </td>
                            <td>
                                <span class="status muted">{{ $perf['top_selling_color'] ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="status muted">{{ $perf['top_selling_size'] ? 'Size '.$perf['top_selling_size'] : '—' }}</span>
                            </td>
                            <td>
                                <span class="mono" style="color:var(--lime)">+{{ $perf['last_30_day_sales'] }} đôi</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted">Chưa có số liệu hiệu suất sản phẩm.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 5: TEAM PROFILES & CUSTOMIZATION STATION                              --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-teams">
    {{-- 8. TEAM PROFILES --}}
    <section class="panel" id="teams">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🛡 HỒ SƠ ĐỘI BÓNG (TEAM PROFILES)</span>
                <div class="muted">Danh sách đội bóng, danh sách cầu thủ in áo và tạo đơn hàng nháp cho toàn đội</div>
            </div>
            <a href="{{ route('admin.customers.index') }}" class="btn lime">Chọn khách hàng tạo đội</a>
        </div>

        @forelse($teamsList as $team)
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:20px;margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;margin-bottom:16px;border-bottom:1px solid var(--border-panel);padding-bottom:14px">
                    <div style="display:flex;align-items:center;gap:14px">
                        <div style="width:48px;height:48px;border-radius:8px;background:var(--bg-panel);border:1px solid var(--border-panel);display:flex;align-items:center;justify-content:center;font-size:22px">
                            {{ $team->logo ? '🛡' : '⚽' }}
                        </div>
                        <div>
                            <h3 style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:4px">
                                {{ $team->team_name }}
                            </h3>
                            <div class="muted" style="font-size:12px">
                                Đội trưởng: <b>{{ $team->captain ?? 'Chưa cập nhật' }}</b> · SĐT: <span class="mono">{{ $team->phone ?? '—' }}</span> · Khách hàng: <b>{{ $team->owner?->name }}</b>
                            </div>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="button" class="btn lime btn-draft-reorder" data-team-id="{{ $team->id }}" data-team-name="{{ $team->team_name }}">
                            ⚡ TẠO ĐƠN NHÁP TỪ ĐỘI
                        </button>
                    </div>
                </div>

                {{-- Members Roster Table --}}
                <div style="font:700 12px/1 'Oswald',sans-serif;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;color:var(--text-sub)">
                    Danh sách cầu thủ & áo đấu ({{ $team->members->count() }} thành viên)
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cầu thủ</th>
                                <th>Tên in áo</th>
                                <th>Số áo</th>
                                <th>Cỡ áo</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($team->members as $member)
                                <tr>
                                    <td><b style="color:var(--text-main)">{{ $member->player_name }}</b></td>
                                    <td><span class="mono" style="color:var(--lime);font-weight:700">{{ $member->shirt_name ?: '—' }}</span></td>
                                    <td><span class="mono" style="font-size:14px;font-weight:700">{{ $member->shirt_number ?? '—' }}</span></td>
                                    <td><span class="status muted">{{ $member->shirt_size ?: '—' }}</span></td>
                                    <td><span class="muted">{{ $member->notes ?: '—' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">Chưa có thành viên trong đội.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:40px 20px;background:var(--bg-panel-sub);border:1px dashed var(--border-panel);border-radius:8px">
                <p class="muted" style="margin-bottom:12px">Chưa có hồ sơ đội bóng nào được tạo.</p>
                <a href="{{ route('admin.customers.index') }}" class="btn lime">Xem danh sách khách hàng để tạo hồ sơ đội</a>
            </div>
        @endforelse
    </section>

    {{-- 9. CUSTOMIZATION STATION --}}
    <section class="panel" id="customization">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🎽 TRẠM CÁ NHÂN HÓA & IN ẤN (CUSTOMIZATION STATION)</span>
                <div class="muted">Quy trình kiểm soát in ấn tên, số áo, logo và phê duyệt market 6 bước chuẩn mực</div>
            </div>
        </div>

        {{-- Workflow Stepper Reference --}}
        <div class="workflow-stepper">
            <div class="wf-step">1. Chờ thiết kế</div>
            <span class="wf-arrow">→</span>
            <div class="wf-step">2. Chờ khách duyệt</div>
            <span class="wf-arrow">→</span>
            <div class="wf-step">3. Đã duyệt</div>
            <span class="wf-arrow">→</span>
            <div class="wf-step">4. Đang in</div>
            <span class="wf-arrow">→</span>
            <div class="wf-step">5. Kiểm tra QC</div>
            <span class="wf-arrow">→</span>
            <div class="wf-step">6. Hoàn tất</div>
        </div>

        {{-- Jobs List --}}
        @forelse($customizationJobs as $job)
            @php
                $statusLabels = [
                    'design_pending' => 'Chờ thiết kế',
                    'customer_approval' => 'Chờ khách duyệt',
                    'approved' => 'Đã duyệt',
                    'printing' => 'Đang in',
                    'quality_check' => 'Kiểm tra QC',
                    'completed' => 'Hoàn tất'
                ];
                $nextStatusMap = [
                    'design_pending' => 'customer_approval',
                    'customer_approval' => 'approved',
                    'approved' => 'printing',
                    'printing' => 'quality_check',
                    'quality_check' => 'completed'
                ];
                $nextStep = $nextStatusMap[$job->status] ?? null;
            @endphp
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px;margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:12px">
                    <div>
                        <div style="font:700 16px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:4px">
                            Đơn hàng #{{ $job->order?->number }} — Khách: {{ $job->order?->user?->name ?? 'Khách lẻ' }}
                        </div>
                        <div class="muted" style="font-size:12px">
                            Sản phẩm: <b>{{ $job->orderItem?->product_name ?? 'Áo đấu custom' }}</b>
                        </div>
                    </div>
                    <span class="status {{ $job->status === 'completed' ? 'completed' : 'pending' }}">
                        {{ $statusLabels[$job->status] ?? ($job->status ? ucfirst(str_replace('_', ' ', $job->status)) : 'Khác') }}
                    </span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:12px;background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:6px;padding:12px;margin-bottom:14px">
                    <div>
                        <span class="muted" style="font-size:10px;text-transform:uppercase">Tên in</span>
                        <div class="mono" style="font-size:16px;font-weight:700;color:var(--lime)">{{ $job->print_name ?: '—' }}</div>
                    </div>
                    <div>
                        <span class="muted" style="font-size:10px;text-transform:uppercase">Số in</span>
                        <div class="mono" style="font-size:16px;font-weight:700;color:var(--text-main)">{{ $job->shirt_number ?: '—' }}</div>
                    </div>
                    <div>
                        <span class="muted" style="font-size:10px;text-transform:uppercase">Màu & Font</span>
                        <div style="font-size:12px">{{ $job->print_color ?: 'Mặc định' }} · {{ $job->font ?: 'Chuẩn' }}</div>
                    </div>
                    <div>
                        <span class="muted" style="font-size:10px;text-transform:uppercase">Ghi chú QC</span>
                        <div style="font-size:12px" class="muted">{{ $job->notes ?: 'Không có ghi chú' }}</div>
                    </div>
                </div>

                @if($nextStep)
                    <div style="display:flex;justify-content:flex-end">
                        <form method="POST" action="{{ route('admin.customization-jobs.status', $job) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStep }}">
                            <button type="submit" class="btn lime">
                                CHUYỂN TRẠNG THÁI: {{ $statusLabels[$nextStep] ?? 'TIẾP THEO' }} →
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div style="text-align:center;padding:40px 20px;background:var(--bg-panel-sub);border:1px dashed var(--border-panel);border-radius:8px">
                <p class="muted">Hiện tại chưa có công việc in ấn tùy biến nào.</p>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px">
                    Các đơn hàng yêu cầu in tên & số áo sẽ hiển thị tự động tại trạm cá nhân hóa này.
                </div>
            </div>
        @endforelse
    </section>
</div>

{{-- Pass authoritative Revenue Intelligence data to JavaScript for instant reactive charts --}}
<script>
window.revenueIntelligenceData = @json($revIntel);

document.addEventListener('DOMContentLoaded', () => {
    // 1. Tab Switching logic (supports URL hash, ?tab=..., and cross-pane anchors)
    const tabBtns = document.querySelectorAll('.dash-tab-btn');
    const tabPanes = document.querySelectorAll('.dash-tab-pane');

    function activateTab(tabId) {
        tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === tabId));
        tabPanes.forEach(p => p.classList.toggle('active', p.id === `tab-${tabId}`));
    }

    function resolveTabTarget(rawTarget) {
        if (!rawTarget) return null;
        const clean = String(rawTarget).replace(/^#/, '').trim().toLowerCase();
        if (!clean) return null;

        const aliases = {
            'finance': { tab: 'revenue', anchor: 'finance' },
            'revenue': { tab: 'revenue', anchor: null },
            'restock': { tab: 'inventory', anchor: 'restock' },
            'inventory': { tab: 'inventory', anchor: null },
            'customization': { tab: 'teams', anchor: 'customization' },
            'teams': { tab: 'teams', anchor: null },
            'overview': { tab: 'overview', anchor: null },
            'performance': { tab: 'performance', anchor: null },
        };

        if (aliases[clean]) return aliases[clean];

        if (document.getElementById(`tab-${clean}`)) {
            return { tab: clean, anchor: null };
        }

        const anchorEl = document.getElementById(clean);
        if (anchorEl) {
            const parentPane = anchorEl.closest('.dash-tab-pane');
            if (parentPane && parentPane.id.startsWith('tab-')) {
                return { tab: parentPane.id.replace('tab-', ''), anchor: clean };
            }
        }

        return null;
    }

    function handleTabNavigation(rawTarget) {
        const resolved = resolveTabTarget(rawTarget);
        if (!resolved) return;
        activateTab(resolved.tab);

        if (resolved.anchor) {
            setTimeout(() => {
                const el = document.getElementById(resolved.anchor);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 80);
        }
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const t = btn.dataset.tab;
            activateTab(t);
            history.replaceState(null, '', '#' + t);
        });
    });

    // Check URL params and hash on page load
    const urlParams = new URLSearchParams(window.location.search);
    const paramTab = urlParams.get('tab');
    const hashTab = window.location.hash;
    const initialTarget = hashTab || paramTab;
    if (initialTarget) {
        handleTabNavigation(initialTarget);
    }

    // Listen to hashchange event (e.g. sidebar navigation while on the same page)
    window.addEventListener('hashchange', () => {
        if (window.location.hash) {
            handleTabNavigation(window.location.hash);
        }
    });

    // 2. Revenue Intelligence Interactive Chart
    const rangeBtns = document.querySelectorAll('.rev-range-btn');
    const metricBtns = document.querySelectorAll('.rev-metric-btn');
    const chartBarsContainer = document.getElementById('chartBarsContainer');
    const chartTotalDisplay = document.getElementById('chartTotalDisplay');
    const chartPeriodSummary = document.getElementById('chartPeriodSummary');
    const paymentBreakdownCards = document.getElementById('paymentBreakdownCards');

    let currentRange = '30';
    let currentMetric = 'revenue'; // 'revenue' | 'orders' | 'aov'

    function renderChart() {
        const data = window.revenueIntelligenceData[currentRange];
        if (!data) return;

        const periods = data.periods || [];
        const breakdown = data.payment_breakdown || {};

        // Calculate metric values
        let values = [];
        let summaryText = '';
        let totalFormatted = '';

        if (currentMetric === 'revenue') {
            values = periods.map(p => p.completed_revenue);
            summaryText = `Tổng doanh thu hoàn tất (${currentRange === '3m' ? '3 tháng' : (currentRange === '12m' ? '12 tháng' : currentRange + ' ngày')})`;
            totalFormatted = (data.totals?.completed_revenue || 0).toLocaleString('vi-VN') + ' ₫';
        } else if (currentMetric === 'orders') {
            values = periods.map(p => p.order_count);
            summaryText = `Tổng đơn hàng (${currentRange === '3m' ? '3 tháng' : (currentRange === '12m' ? '12 tháng' : currentRange + ' ngày')})`;
            totalFormatted = (data.totals?.order_count || 0) + ' đơn';
        } else {
            values = periods.map(p => p.average_order_value);
            summaryText = `Giá trị đơn trung bình (AOV)`;
            totalFormatted = (data.totals?.average_order_value || 0).toLocaleString('vi-VN') + ' ₫';
        }

        if (chartPeriodSummary) chartPeriodSummary.innerText = summaryText;
        if (chartTotalDisplay) chartTotalDisplay.innerText = totalFormatted;

        const maxVal = Math.max(...values, 1);

        // Render Bars
        if (chartBarsContainer) {
            chartBarsContainer.innerHTML = periods.map((p, idx) => {
                const val = values[idx];
                const height = Math.max(4, Math.round((val / maxVal) * 160));
                const label = p.period.length > 5 ? p.period.slice(-5) : p.period;
                const tooltip = `${p.period}: ${currentMetric === 'orders' ? val + ' đơn' : val.toLocaleString('vi-VN') + ' ₫'}`;
                return `
                    <div class="chart-bar-col" title="${tooltip}">
                        <div class="chart-bar-rect" style="height:${height}px"></div>
                        <span class="chart-x-label">${label}</span>
                    </div>
                `;
            }).join('');
        }

        // Render Payment Breakdown
        if (paymentBreakdownCards) {
            const mLabels = {
                cod: 'Thanh toán khi nhận (COD)',
                momo: 'Ví điện tử MoMo',
                bank_qr_payos: 'Chuyển khoản Bank QR / payOS'
            };
            const sumAmounts = Object.values(breakdown).reduce((acc, b) => acc + (b.amount || 0), 0) || 1;

            paymentBreakdownCards.innerHTML = Object.keys(mLabels).map(mKey => {
                const mData = breakdown[mKey] || { amount: 0, order_count: 0 };
                const pct = Math.round((mData.amount / sumAmounts) * 100);
                return `
                    <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                        <div class="muted" style="font-size:11px;font-weight:700;margin-bottom:6px">${mLabels[mKey]}</div>
                        <div class="mono" style="font-size:18px;font-weight:700;color:var(--text-main);margin-bottom:4px">
                            ${mData.amount.toLocaleString('vi-VN')} ₫
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:8px">
                            <span>${mData.order_count} đơn hoàn tất</span>
                            <span class="mono">${pct}%</span>
                        </div>
                        <div style="height:4px;background:var(--bg-panel);border-radius:2px;overflow:hidden">
                            <div style="height:100%;width:${pct}%;background:var(--lime)"></div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    rangeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            rangeBtns.forEach(b => b.classList.remove('lime'));
            btn.classList.add('lime');
            currentRange = btn.dataset.range;
            renderChart();
        });
    });

    metricBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            metricBtns.forEach(b => b.classList.remove('lime'));
            btn.classList.add('lime');
            currentMetric = btn.dataset.metric;
            renderChart();
        });
    });

    // 3. Restock Radar Quick Filters
    const restockFilterBtns = document.querySelectorAll('.restock-filter-btn');
    const restockRows = document.querySelectorAll('#restockTable tbody tr');

    restockFilterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            restockFilterBtns.forEach(b => b.classList.remove('lime'));
            btn.classList.add('lime');
            const f = btn.dataset.filter;

            restockRows.forEach(row => {
                if (f === 'all') {
                    row.style.display = '';
                } else if (f === 'restock_recommended') {
                    row.style.display = row.dataset.rec === 'restock_recommended' ? '' : 'none';
                } else if (f === 'important') {
                    row.style.display = ['40', '41'].includes(row.dataset.size) ? '' : 'none';
                }
            });
        });
    });

    // 4. Draft Reorder from Team Profile Action
    document.querySelectorAll('.btn-draft-reorder').forEach(btn => {
        btn.addEventListener('click', async () => {
            const teamId = btn.dataset.teamId;
            const teamName = btn.dataset.teamName;
            btn.disabled = true;
            btn.innerText = 'ĐANG XỬ LÝ...';

            try {
                const res = await fetch(`/admin/teams/${teamId}/draft-reorder`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                alert(`✓ Đã tạo yêu cầu đơn hàng nháp từ đội bóng "${teamName}" thành công! Trạng thái: ${data.status}, Số lượng thành viên: ${data.members?.length || 0}.`);
            } catch (err) {
                alert('Không thể kết nối máy chủ để tạo đơn nháp.');
            } finally {
                btn.disabled = false;
                btn.innerText = '⚡ TẠO ĐƠN NHÁP TỪ ĐỘI';
            }
        });
    });
});
</script>
@endsection
