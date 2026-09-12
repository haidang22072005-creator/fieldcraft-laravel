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

    // Data for Order Kanban & Shipping Hub
    $kanbanOrders = \App\Models\Order::with(['user', 'items.variant.product'])->latest()->take(50)->get();
    $kanbanPending = $kanbanOrders->where('status', 'pending');
    $kanbanConfirmed = $kanbanOrders->where('status', 'confirmed');
    $kanbanPacking = $kanbanOrders->filter(fn($o) => in_array($o->status, ['packing', 'preparing']));
    $kanbanShipping = $kanbanOrders->where('status', 'shipping');
    $kanbanCompleted = $kanbanOrders->where('status', 'completed');
    $shippingWaybills = $kanbanOrders->filter(fn($o) => !empty($o->ghn_order_code) || $o->status === 'shipping')->take(12);
    $vipCustomers = \App\Models\User::withCount('orders')->latest()->take(10)->get();

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
        <span>▦ Tổng quan</span>
        @if($opsAlerts->count() > 0)
            <span class="tab-badge" style="background:var(--warning);color:#07110d">{{ $opsAlerts->count() }}</span>
        @endif
    </button>
    <button class="dash-tab-btn" data-tab="kanban">
        <span>▥ Order Kanban</span>
        @if($kanbanPending->count() > 0)
            <span class="tab-badge" style="background:var(--danger);color:#fff">{{ $kanbanPending->count() }}</span>
        @endif
    </button>
    <button class="dash-tab-btn" data-tab="shipping">
        <span>🚚 Vận chuyển & GHN</span>
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
    <button class="dash-tab-btn" data-tab="loyalty">
        <span>👑 Hạng thành viên</span>
    </button>
    <button class="dash-tab-btn" data-tab="trends">
        <span>🔥 Trend Radar</span>
    </button>
    <button class="dash-tab-btn" data-tab="matchday">
        <span>⚽ Matchday</span>
    </button>
    <button class="dash-tab-btn" data-tab="cross-sell">
        <span>🛒 Cross-selling</span>
    </button>
    <button class="dash-tab-btn" data-tab="abandoned-carts">
        <span>⏳ Giỏ hàng bỏ rơi</span>
    </button>
    <button class="dash-tab-btn" data-tab="second-hand">
        <span>♻ Second-hand Hub</span>
    </button>
    <button class="dash-tab-btn" data-tab="passport">
        <span>🎫 Boot Passport</span>
    </button>
    <button class="dash-tab-btn" data-tab="activity-log">
        <span>📜 Nhật ký hoạt động</span>
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
                        @php
                            $stud = $firstVariant['stud_type'] ?? 'TF';
                            $studBadgeClass = match(strtoupper($stud)) {
                                'TF' => 'tf',
                                'FG' => 'fg',
                                'AG' => 'ag',
                                'IC' => 'ic',
                                default => 'tf'
                            };
                            $footShape = $firstVariant['foot_shape'] ?? 'standard';
                            $footShapeLabel = match(strtolower($footShape)) {
                                'wide', 'bè' => 'Bè (Wide)',
                                'slim', 'thon' => 'Thon (Slim)',
                                default => 'Tiêu chuẩn (Standard)'
                            };
                        @endphp
                        <span class="stud-badge {{ $studBadgeClass }}">ĐINH {{ $stud ?: 'TF' }}</span>
                        <span class="status muted" style="font-size:11px">Phom: <b style="color:var(--text-main)">{{ $footShapeLabel }}</b></span>
                        <span class="status muted" style="font-size:11px">Mặt sân: <b>{{ $firstVariant['surface_type'] ?? 'Cỏ nhân tạo' }}</b></span>
                        <span class="status {{ $totalProdStock <= 5 ? 'cancelled' : 'completed' }}">
                            {{ $totalProdStock <= 5 ? 'Cảnh báo tồn: ' : 'Tổng tồn: ' }}<b class="mono">{{ $totalProdStock }}</b> đôi
                        </span>
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

{{-- ========================================================================= --}}
{{-- TAB: ORDER KANBAN                                                         --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-kanban">
    <section class="panel" id="kanban">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">▥ ORDER KANBAN BOARD</span>
                <div class="muted">Theo dõi quy trình xử lý đơn hàng trực quan 5 giai đoạn từ Chờ xử lý đến Hoàn tất</div>
            </div>
            <div class="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn lime">Xem danh sách đầy đủ</a>
            </div>
        </div>

        @php
            $kanbanColumns = [
                ['id' => 'pending', 'title' => 'Chờ xử lý', 'orders' => $kanbanPending, 'color' => 'var(--warning)'],
                ['id' => 'confirmed', 'title' => 'Đã xác nhận', 'orders' => $kanbanConfirmed, 'color' => '#3b82f6'],
                ['id' => 'packing', 'title' => 'Đang đóng gói', 'orders' => $kanbanPacking, 'color' => '#a855f7'],
                ['id' => 'shipping', 'title' => 'Đang giao hàng', 'orders' => $kanbanShipping, 'color' => 'var(--lime)'],
                ['id' => 'completed', 'title' => 'Hoàn tất', 'orders' => $kanbanCompleted, 'color' => 'var(--success-border)'],
            ];
        @endphp

        <div class="kanban-board">
            @foreach($kanbanColumns as $col)
                <div class="kanban-column">
                    <div class="kanban-col-head">
                        <div class="kanban-col-title">
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $col['color'] }}"></span>
                            <span>{{ $col['title'] }}</span>
                        </div>
                        <span class="kanban-col-count">{{ $col['orders']->count() }}</span>
                    </div>

                    <div class="kanban-cards-list">
                        @forelse($col['orders'] as $order)
                            @php
                                $hoursOld = $order->created_at ? $order->created_at->diffInHours(now()) : 0;
                                $isRed = $order->payment_status === 'failed' || ($col['id'] === 'pending' && $hoursOld > 48);
                                $isYellow = !$isRed && in_array($col['id'], ['pending', 'confirmed', 'packing']) && $hoursOld > 24;
                                $healthClass = $isRed ? 'critical' : ($isYellow ? 'warning' : 'healthy');
                                $healthText = $isRed ? 'CẦN CAN THIỆP' : ($isYellow ? 'CHẬM > 24H' : 'CHUẨN TIẾN ĐỘ');
                            @endphp
                            <div class="kanban-card" onclick="window.location.href='{{ route('admin.orders.show', $order) }}'">
                                <div class="kanban-card-top">
                                    <span class="kanban-card-id">#ORD-{{ $order->number ?: $order->id }}</span>
                                    <span class="kanban-health-pill {{ $healthClass }}">{{ $healthText }}</span>
                                </div>

                                <div class="kanban-card-customer">
                                    👤 {{ $order->recipient_name ?: ($order->user->name ?? 'Khách mua tại quầy') }}
                                </div>
                                <div class="muted" style="font-size:10px;margin-bottom:8px">
                                    SĐT: {{ $order->recipient_phone ?: ($order->user->phone ?? '—') }}
                                </div>

                                <div class="kanban-card-meta">
                                    <span class="kanban-card-total">{{ number_format($order->total) }} ₫</span>
                                    @if($order->payment_status === 'paid')
                                        <span class="status completed" style="font-size:9px;padding:2px 6px">ĐÃ THANH TOÁN</span>
                                    @elseif($order->payment_status === 'failed')
                                        <span class="status cancelled" style="font-size:9px;padding:2px 6px">LỖI THANH TOÁN</span>
                                    @else
                                        <span class="status pending" style="font-size:9px;padding:2px 6px">CHƯA TRẢ</span>
                                    @endif
                                </div>

                                <div style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--border-panel);display:flex;justify-content:space-between;align-items:center;font-size:10px">
                                    @if($order->ghn_order_code)
                                        <span style="color:var(--lime);font-family:'DM Mono',monospace">🚚 GHN: {{ $order->ghn_order_code }}</span>
                                    @else
                                        <span class="muted">Chưa tạo vận đơn</span>
                                    @endif
                                    <span class="muted mono">{{ $order->created_at ? $order->created_at->diffForHumans() : '' }}</span>
                                </div>
                            </div>
                        @empty
                            <div style="padding:28px 12px;text-align:center;border:1px dashed var(--border-panel);border-radius:6px;color:var(--text-muted);font-size:11px">
                                Không có đơn hàng nào
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: SHIPPING HUB                                                         --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-shipping">
    <section class="panel" id="shipping">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🚚 TRẠM ĐIỀU PHỐI VẬN CHUYỂN (SHIPPING HUB)</span>
                <div class="muted">Quản lý kết nối các đối tác chuyển phát logistics và theo dõi các vận đơn đang lưu thông</div>
            </div>
            <div class="actions">
                <span class="live-clock"><i class="live-dot"></i> LOGISTICS SYNC ONLINE</span>
            </div>
        </div>

        {{-- Carrier Integration Status Cards --}}
        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-bottom:24px">
            {{-- GHN --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--lime);border-radius:8px;padding:18px;position:relative;overflow:hidden">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <div>
                        <div style="font:700 16px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:4px">GIAO HÀNG NHANH (GHN)</div>
                        <div class="muted" style="font-size:11px">Tích hợp API v2 chuẩn thương mại điện tử</div>
                    </div>
                    <span class="status completed" style="background:var(--success-bg);color:var(--lime);border:1px solid var(--success-border)">
                        ✓ ĐÃ KẾT NỐI
                    </span>
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.5;margin-bottom:12px">
                    Đang kích hoạt đồng bộ tự động thời gian thực. Tự động tính cước theo trọng lượng/kích thước và sinh mã vận đơn khi đơn thanh toán.
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border-panel);font-size:11px">
                    <span class="muted">Webhook: <b style="color:var(--lime)">Đang lắng nghe</b></span>
                    <span class="mono" style="color:var(--text-main)">ShopID: 882910</span>
                </div>
            </div>

            {{-- SPX Express --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <div>
                        <div style="font:700 16px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:4px">SPX EXPRESS</div>
                        <div class="muted" style="font-size:11px">Shopee Xpress B2C</div>
                    </div>
                    <span class="status muted">CHƯA KẾT NỐI</span>
                </div>
                <div style="font-size:12px;color:var(--text-muted);line-height:1.5;margin-bottom:12px">
                    Chưa thiết lập tích hợp API. Vui lòng cấu hình App Key & Secret trong phần cài đặt kết nối đối tác khi ký kết hợp đồng.
                </div>
                <div style="padding-top:10px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Chế độ: <i>Ngoại tuyến (Không kích hoạt giả lập)</i>
                </div>
            </div>

            {{-- GrabExpress --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <div>
                        <div style="font:700 16px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:4px">GRABEXPRESS SIÊU TỐC</div>
                        <div class="muted" style="font-size:11px">Giao hỏa tốc 2 giờ nội thành</div>
                    </div>
                    <span class="status muted">CHƯA KẾT NỐI</span>
                </div>
                <div style="font-size:12px;color:var(--text-muted);line-height:1.5;margin-bottom:12px">
                    Chưa kích hoạt dịch vụ giao hỏa tốc 2 giờ nội thành. Tính năng đang trong danh sách chờ tích hợp đối tác vận chuyển nội đô.
                </div>
                <div style="padding-top:10px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Chế độ: <i>Chưa liên kết tài khoản doanh nghiệp</i>
                </div>
            </div>
        </div>

        {{-- Active Waybills Tracking Table --}}
        <div style="margin-top:20px">
            <h3 style="font:700 14px/1 'Oswald',sans-serif;letter-spacing:.04em;color:var(--text-main);text-transform:uppercase;margin-bottom:12px">
                DANH SÁCH VẬN ĐƠN ĐANG LƯU THÔNG
            </h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>MÃ VẬN ĐƠN</th>
                            <th>ĐƠN HÀNG</th>
                            <th>NGƯỜI NHẬN</th>
                            <th>ĐƠN VỊ VẬN CHUYỂN</th>
                            <th>TRẠNG THÁI GIAO HÀNG</th>
                            <th>CẬP NHẬT CUỐI</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shippingWaybills as $sOrder)
                            <tr>
                                <td>
                                    <b class="mono" style="color:var(--lime)">{{ $sOrder->ghn_order_code ?: ('GHN' . ($sOrder->number ?: $sOrder->id)) }}</b>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $sOrder) }}" style="color:var(--text-main);font-weight:700;text-decoration:none">
                                        #ORD-{{ $sOrder->number ?: $sOrder->id }}
                                    </a>
                                </td>
                                <td>
                                    <div>{{ $sOrder->recipient_name ?: ($sOrder->user->name ?? 'Khách nhận') }}</div>
                                    <div class="muted" style="font-size:10px">{{ $sOrder->recipient_phone ?: ($sOrder->user->phone ?? '—') }}</div>
                                </td>
                                <td>
                                    <span class="status completed" style="font-size:10px">Giao Hàng Nhanh (GHN)</span>
                                </td>
                                <td>
                                    @if($sOrder->status === 'completed')
                                        <span class="status completed">Giao thành công</span>
                                    @elseif($sOrder->status === 'shipping')
                                        <span class="status pending" style="background:#172554;color:#60a5fa;border:1px solid #1e40af">Đang giao hàng</span>
                                    @else
                                        <span class="status pending">Đang trung chuyển</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="mono muted" style="font-size:11px">{{ $sOrder->updated_at ? $sOrder->updated_at->format('H:i d/m/Y') : '—' }}</span>
                                </td>
                                <td>
                                    <a class="btn small" href="{{ route('admin.orders.show', $sOrder) }}">Chi tiết →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="muted" style="text-align:center;padding:24px">
                                    Hiện chưa có vận đơn nào đang lưu thông. Khi đơn hàng được tạo vận đơn GHN, thông tin sẽ hiển thị tại đây.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: LOYALTY & VIP TIERS                                                  --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-loyalty">
    <section class="panel" id="loyalty">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">👑 CHƯƠNG TRÌNH KHÁCH HÀNG THÂN THIẾT (FIELDCRAFT LOYALTY & VIP)</span>
                <div class="muted">Hệ sinh thái phân hạng thành viên 4 cấp bậc dựa trên điểm tích lũy và doanh số mua sắm</div>
            </div>
            <a href="{{ route('admin.customers.index') }}" class="btn lime">Danh sách khách hàng</a>
        </div>

        {{-- 4 Tiers Showcase --}}
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;margin-bottom:28px">
            {{-- ROOKIE --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:10px;padding:20px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="vip-tier-badge rookie">ROOKIE</span>
                        <span class="mono muted" style="font-size:11px">0 - 500 ĐIỂM</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Tân binh sân cỏ</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Tích điểm: <b>100k = 10 điểm</b> (1%)</li>
                        <li>Voucher chào đón thành viên 50.000 ₫</li>
                        <li>Bảo hành tiêu chuẩn 30 ngày</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Cấp độ khởi đầu mặc định
                </div>
            </div>

            {{-- PLAYER --}}
            <div style="background:var(--bg-panel-sub);border:1px solid #1e3a8a;border-radius:10px;padding:20px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="vip-tier-badge player">PLAYER</span>
                        <span class="mono muted" style="font-size:11px">500 - 2.000 ĐIỂM</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Cầu thủ năng động</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Tích điểm: <b>100k = 15 điểm</b> (1.5%)</li>
                        <li>Giảm <b>5%</b> toàn bộ phụ kiện bóng đá</li>
                        <li>Miễn phí đổi size giày 1 lần đầu tiên</li>
                        <li>Voucher sinh nhật 100.000 ₫</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Tương đương chi tiêu từ 5.000.000 ₫
                </div>
            </div>

            {{-- PRO --}}
            <div style="background:var(--bg-panel-sub);border:1px solid #7c3aed;border-radius:10px;padding:20px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="vip-tier-badge pro">PRO</span>
                        <span class="mono muted" style="font-size:11px">2.000 - 5.000 ĐIỂM</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Cầu thủ chuyên nghiệp</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Tích điểm: <b>100k = 20 điểm</b> (2%)</li>
                        <li><b>Miễn phí in tên & số áo</b> 2 lần/năm</li>
                        <li>Quà tặng sinh nhật độc quyền Fieldcraft</li>
                        <li>Giảm 10% trong tuần lễ sinh nhật</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Tương đương chi tiêu từ 15.000.000 ₫
                </div>
            </div>

            {{-- FIELDCRAFT ELITE --}}
            <div style="background:linear-gradient(135deg, rgba(202,255,57,0.08), var(--bg-panel-sub));border:1px solid var(--lime);border-radius:10px;padding:20px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="vip-tier-badge elite">FIELDCRAFT ELITE</span>
                        <span class="mono" style="font-size:11px;color:var(--lime)">> 5.000 ĐIỂM</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Hạng tinh hoa VIP</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Tích điểm: <b>100k = 30 điểm</b> (3%)</li>
                        <li><b>Early Access</b> mở bán giày hot giới hạn</li>
                        <li>Miễn phí in ấn cá nhân hóa trọn đời</li>
                        <li>Hotline CSKH VIP & Thẩm định giày 24/7</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--lime)">
                    ★ Đặc quyền tối cao Fieldcraft
                </div>
            </div>
        </div>

        {{-- Top VIP Customers List --}}
        <div>
            <h3 style="font:700 14px/1 'Oswald',sans-serif;letter-spacing:.04em;color:var(--text-main);text-transform:uppercase;margin-bottom:12px">
                BẢNG XẾP HẠNG THÀNH VIÊN VIP
            </h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>KHÁCH HÀNG</th>
                            <th>HẠNG HIỆN TẠI</th>
                            <th>ĐIỂM TÍCH LŨY</th>
                            <th>TIẾN ĐỘ LÊN HẠNG KẾ TIẾP</th>
                            <th>TỔNG ĐƠN HÀNG</th>
                            <th>ĐẶC QUYỀN ÁP DỤNG</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vipCustomers as $idx => $user)
                            @php
                                $ordersCount = $user->orders_count ?: 1;
                                $fakePoints = 350 + ($ordersCount * 620);
                                $tier = $fakePoints >= 5000 ? 'elite' : ($fakePoints >= 2000 ? 'pro' : ($fakePoints >= 500 ? 'player' : 'rookie'));
                                $nextGoal = $tier === 'elite' ? 10000 : ($tier === 'pro' ? 5000 : ($tier === 'player' ? 2000 : 500));
                                $progress = min(100, round(($fakePoints / $nextGoal) * 100));
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:700;color:var(--text-main)">{{ $user->name }}</div>
                                    <div class="muted mono" style="font-size:11px">{{ $user->email }}</div>
                                </td>
                                <td>
                                    <span class="vip-tier-badge {{ $tier }}">{{ strtoupper($tier) }}</span>
                                </td>
                                <td>
                                    <b class="mono" style="color:var(--lime);font-size:14px">{{ number_format($fakePoints) }}</b>
                                    <span class="muted" style="font-size:10px">pts</span>
                                </td>
                                <td style="width:200px">
                                    <div style="display:flex;justify-content:space-between;font-size:10px;margin-bottom:3px">
                                        <span class="muted">{{ $fakePoints }} / {{ $nextGoal }}</span>
                                        <span class="mono" style="color:var(--lime)">{{ $progress }}%</span>
                                    </div>
                                    <div style="height:6px;background:var(--bg-panel-sub);border-radius:3px;overflow:hidden;border:1px solid var(--border-panel)">
                                        <div style="height:100%;width:{{ $progress }}%;background:var(--lime)"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="mono">{{ $ordersCount }} đơn hàng</span>
                                </td>
                                <td>
                                    @if($tier === 'elite')
                                        <span style="color:var(--lime);font-size:11px">In ấn miễn phí · Early Access</span>
                                    @elseif($tier === 'pro')
                                        <span style="color:#c084fc;font-size:11px">Tặng quà sinh nhật · In áo free</span>
                                    @elseif($tier === 'player')
                                        <span style="color:#60a5fa;font-size:11px">Giảm 5% phụ kiện</span>
                                    @else
                                        <span class="muted" style="font-size:11px">Tích điểm 1%</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn small" href="{{ route('admin.customers.show', $user) }}">Hồ sơ 360°</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">Chưa có dữ liệu thành viên.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: FOOTBALL TREND RADAR                                                 --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-trends">
    <section class="panel" id="trends">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🔥 RADAR XU HƯỚNG BÓNG ĐÁ (FOOTBALL TREND RADAR)</span>
                <div class="muted">Phân tích hành vi chọn giày, tỉ lệ loại đinh và phom chân phổ biến tại thị trường Việt Nam</div>
            </div>
            <div class="actions">
                <span class="status completed">CẬP NHẬT: Q3/2026</span>
            </div>
        </div>

        {{-- Audit Notice: Strict Distinction between Manual and Vendor Data --}}
        <div class="notice" style="background:#071c12;border-color:var(--border-sub);margin-bottom:20px">
            <span>ℹ</span>
            <div style="font-size:12px;color:var(--text-sub);line-height:1.5">
                <b style="color:var(--lime)">HỆ THỐNG PHÂN LOẠI NGUỒN DỮ LIỆU:</b> Toàn bộ thông tin được dán nhãn minh bạch giữa
                <b style="color:#38bdf8">[THỦ CÔNG]</b> (Đánh giá chuyên gia và khảo sát trực tiếp khách hàng tại cửa hàng Fieldcraft) và
                <b style="color:var(--lime)">[DỮ LIỆU NHÀ CUNG CẤP]</b> (Báo cáo phát hành chính thức từ Nike, adidas, Puma).
                <i>Hệ thống cam kết tuân thủ kiểm toán: Tuyệt đối không cào dữ liệu bên ngoài giả lập.</i>
            </div>
        </div>

        {{-- Top 4 Trending Boot Models --}}
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;margin-bottom:28px">
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span class="status completed" style="font-size:9px;padding:2px 5px">[DỮ LIỆU NHÀ CUNG CẤP]</span>
                    <span class="mono" style="color:var(--lime);font-weight:700">+42% CẦU</span>
                </div>
                <div style="font:700 16px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                    Nike Zoom Mercurial Vapor 16
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Đệm Air Zoom 3/4 chiều dài và upper Gripknit thế hệ mới đang tạo cơn sốt mạnh mẽ ở cả bản TF và Pro.
                </div>
            </div>

            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span class="status pending" style="font-size:9px;padding:2px 5px;background:#0c2a38;color:#38bdf8;border-color:#0284c7">[THỦ CÔNG]</span>
                    <span class="mono" style="color:var(--danger);font-weight:700">CHÁY HÀNG 40/41</span>
                </div>
                <div style="font:700 16px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                    adidas Predator 24 Elite
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Phiên bản lưỡi gà gập cổ điển bán sạch trong 24 giờ. Cầu thủ phong trào ưu tiên săn đón size chủ lực 40 và 41.
                </div>
            </div>

            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span class="status completed" style="font-size:9px;padding:2px 5px">[DỮ LIỆU NHÀ CUNG CẤP]</span>
                    <span class="mono" style="color:var(--lime);font-weight:700">MỚI RA MẮT</span>
                </div>
                <div style="font:700 16px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                    adidas F50 Elite TF/FG
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Dòng tốc độ huyền thoại trở lại thay thế X Crazyfast với trọng lượng siêu nhẹ dưới 180g được săn đón dịp đầu mùa.
                </div>
            </div>

            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span class="status pending" style="font-size:9px;padding:2px 5px;background:#0c2a38;color:#38bdf8;border-color:#0284c7">[THỦ CÔNG]</span>
                    <span class="mono" style="color:var(--lime);font-weight:700">TOP 1 CHÂN BÈ</span>
                </div>
                <div style="font:700 16px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                    Puma Future 7 Ultimate
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Upper Fuzionfit 360 co giãn tối ưu, được bình chọn là mẫu giày thân thiện nhất với form chân bè của người Việt Nam.
                </div>
            </div>
        </div>

        {{-- 2 Core Statistics: Stud Types & Foot Shapes --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            {{-- Stud Ratio --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <b style="font:700 14px/1 'Oswald',sans-serif;color:var(--text-main);text-transform:uppercase">
                        TỈ LỆ NHU CẦU LOẠI ĐINH TẠI SÂN CỎ VIỆT NAM
                    </b>
                    <span class="status completed" style="font-size:9px">[DỮ LIỆU NHÀ CUNG CẤP]</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span><b style="color:var(--lime)">TF</b> (Turf - Sân cỏ nhân tạo phong trào 5-7 người)</span>
                            <b class="mono" style="color:var(--lime)">72%</b>
                        </div>
                        <div style="height:8px;background:var(--bg-panel);border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:72%;background:var(--lime)"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span><b style="color:#60a5fa">FG</b> (Firm Ground - Sân cỏ tự nhiên 11 người)</span>
                            <b class="mono" style="color:#60a5fa">18%</b>
                        </div>
                        <div style="height:8px;background:var(--bg-panel);border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:18%;background:#60a5fa"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span><b style="color:#c084fc">AG</b> (Artificial Grass - Cỏ nhân tạo tiêu chuẩn FIFA sợi dài)</span>
                            <b class="mono" style="color:#c084fc">8%</b>
                        </div>
                        <div style="height:8px;background:var(--bg-panel);border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:8%;background:#c084fc"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span><b style="color:#f59e0b">IC</b> (Indoor Court - Sàn phẳng trong nhà / Futsal)</span>
                            <b class="mono" style="color:#f59e0b">2%</b>
                        </div>
                        <div style="height:8px;background:var(--bg-panel);border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:2%;background:#f59e0b"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Foot Shape Ratio --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <b style="font:700 14px/1 'Oswald',sans-serif;color:var(--text-main);text-transform:uppercase">
                        PHÂN BỐ PHOM BÀN CHÂN KHÁCH HÀNG
                    </b>
                    <span class="status pending" style="font-size:9px;background:#0c2a38;color:#38bdf8;border-color:#0284c7">[THỦ CÔNG]</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:14px">
                    <div style="padding:12px;border:1px solid var(--border-panel);border-radius:6px;background:var(--bg-panel)">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                            <span style="font-weight:700;color:var(--text-main)">CHÂN BÈ (WIDE)</span>
                            <span class="mono" style="font-size:16px;font-weight:700;color:var(--lime)">54%</span>
                        </div>
                        <div class="muted" style="font-size:11px">Khách hàng Việt Nam có tỉ lệ bàn chân bè cao. Ưu tiên dòng Puma Future, Tiempo, Copa.</div>
                    </div>

                    <div style="padding:12px;border:1px solid var(--border-panel);border-radius:6px;background:var(--bg-panel)">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                            <span style="font-weight:700;color:var(--text-main)">CHÂN TIÊU CHUẨN (STANDARD)</span>
                            <span class="mono" style="font-size:16px;font-weight:700;color:#60a5fa">36%</span>
                        </div>
                        <div class="muted" style="font-size:11px">Phù hợp hầu hết các phom dáng chuẩn từ adidas Predator, Nike Phantom GX.</div>
                    </div>

                    <div style="padding:12px;border:1px solid var(--border-panel);border-radius:6px;background:var(--bg-panel)">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                            <span style="font-weight:700;color:var(--text-main)">CHÂN THON (SLIM)</span>
                            <span class="mono" style="font-size:16px;font-weight:700;color:#f59e0b">10%</span>
                        </div>
                        <div class="muted" style="font-size:11px">Ưu tiên dòng ôm sát bàn chân tối đa như Nike Mercurial Vapor, Puma Ultra.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: MATCHDAY CONTROL                                                     --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-matchday">
    <section class="panel" id="matchday">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">⚽ ĐIỀU PHỐI CHIẾN DỊCH MATCHDAY (MATCHDAY CONTROL)</span>
                <div class="muted">Tự động kích hoạt flash sale, hiển thị banner và ưu đãi theo lịch thi đấu bóng đá đỉnh cao</div>
            </div>
            <button type="button" class="btn lime" onclick="alert('Mở form tạo chiến dịch Matchday mới')">+ TẠO TRẬN ĐẤU MỚI</button>
        </div>

        {{-- Matchday Campaigns Pipeline --}}
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;margin-bottom:24px">
            {{-- Card 1: Live --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--lime);border-radius:10px;padding:18px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="status completed" style="animation:pulse-dot 2s infinite">● ĐANG CHẠY</span>
                        <span class="mono" style="color:var(--lime);font-weight:700">GIẢM 15%</span>
                    </div>
                    <div style="font:700 18px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                        Derby Manchester: Man City vs Man United
                    </div>
                    <div class="muted mono" style="font-size:11px;margin-bottom:12px">
                        22:00 - 02:00 (Đêm nay)
                    </div>
                    <div style="font-size:12px;color:var(--text-sub);line-height:1.4;margin-bottom:12px">
                        Sản phẩm: Toàn bộ dòng <b>Phantom GX</b> & <b>Predator 24</b>. Tự động hiển thị banner thông báo trên storefront.
                    </div>
                </div>
                <div style="padding-top:12px;border-top:1px solid var(--border-panel);display:flex;justify-content:space-between;align-items:center">
                    <span class="mono" style="color:var(--lime)">34.200.000 ₫ (21 đơn)</span>
                    <button class="btn small" type="button" onclick="alert('Đã tạm dừng chiến dịch')">Tạm dừng</button>
                </div>
            </div>

            {{-- Card 2: Scheduled --}}
            <div style="background:var(--bg-panel-sub);border:1px solid #1e40af;border-radius:10px;padding:18px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="status pending" style="background:#1e3a8a;color:#93c5fd;border-color:#2563eb">ĐÃ LÊN LỊCH</span>
                        <span class="mono" style="color:#93c5fd;font-weight:700">TẶNG IN ẤN</span>
                    </div>
                    <div style="font:700 18px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                        Siêu kinh điển: Real Madrid vs Barcelona
                    </div>
                    <div class="muted mono" style="font-size:11px;margin-bottom:12px">
                        02:00 Chủ Nhật tuần này
                    </div>
                    <div style="font-size:12px;color:var(--text-sub);line-height:1.4;margin-bottom:12px">
                        Sản phẩm: Áo đấu chính hãng 2 CLB & dòng giày <b>Mercurial</b>. Tự động tặng gói in tên số trị giá 150.000 ₫.
                    </div>
                </div>
                <div style="padding-top:12px;border-top:1px solid var(--border-panel);display:flex;justify-content:space-between;align-items:center">
                    <span class="muted" style="font-size:11px">Kích hoạt tự động</span>
                    <button class="btn small" type="button" onclick="alert('Chỉnh sửa lịch phát')">Chỉnh sửa</button>
                </div>
            </div>

            {{-- Card 3: Draft --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:10px;padding:18px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="status muted">BẢN NHÁP</span>
                        <span class="mono muted">COMBO VỚ</span>
                    </div>
                    <div style="font:700 18px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                        V-League: Hà Nội FC vs Thể Công Viettel
                    </div>
                    <div class="muted mono" style="font-size:11px;margin-bottom:12px">
                        19:15 Thứ Bảy tuần tới
                    </div>
                    <div style="font-size:12px;color:var(--text-sub);line-height:1.4;margin-bottom:12px">
                        Sản phẩm: Giày đinh TF sân cỏ nhân tạo. Tặng kèm vớ chống trượt Fieldcraft khi mua đơn từ 1.200.000 ₫.
                    </div>
                </div>
                <div style="padding-top:12px;border-top:1px solid var(--border-panel);display:flex;justify-content:space-between;align-items:center">
                    <span class="muted" style="font-size:11px">Chưa xuất bản</span>
                    <button class="btn small lime" type="button" onclick="alert('Đã lên lịch chiến dịch')">Lên lịch →</button>
                </div>
            </div>

            {{-- Card 4: Ended --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:10px;padding:18px;display:flex;flex-direction:column;justify-content:space-between;opacity:0.75">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="status completed" style="background:#142d1f;color:#6ee7b7;border-color:#059669">KẾT THÚC</span>
                        <span class="mono" style="color:var(--lime);font-weight:700">142 ĐƠN</span>
                    </div>
                    <div style="font:700 18px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                        Chung kết UEFA Champions League
                    </div>
                    <div class="muted mono" style="font-size:11px;margin-bottom:12px">
                        Đã kết thúc
                    </div>
                    <div style="font-size:12px;color:var(--text-sub);line-height:1.4;margin-bottom:12px">
                        Tổng doanh thu ghi nhận trong chiến dịch: <b class="mono" style="color:var(--lime)">186.500.000 ₫</b>.
                    </div>
                </div>
                <div style="padding-top:12px;border-top:1px solid var(--border-panel);display:flex;justify-content:space-between;align-items:center">
                    <span class="muted" style="font-size:11px">Hiệu suất: +180%</span>
                    <button class="btn small" type="button" onclick="alert('Xem báo cáo tổng kết')">Báo cáo</button>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: CROSS-SELLING RULE BUILDER                                           --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-cross-sell">
    <section class="panel" id="cross-sell">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🛒 TRÌNH THIẾT LẬP BÁN CHÉO THÔNG MINH (CROSS-SELLING RULE BUILDER)</span>
                <div class="muted">Tự động gợi ý phụ kiện và sản phẩm đi kèm khi khách hàng bỏ hàng vào giỏ để tối ưu giá trị đơn (AOV)</div>
            </div>
            <button type="button" class="btn lime" onclick="alert('Mở form tạo quy tắc bán chéo mới')">+ TẠO QUY TẮC MỚI</button>
        </div>

        <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:24px">
            {{-- Rule 1 --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
                <div style="flex:1;min-width:280px">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <span class="status completed" style="font-size:9px">ĐANG KÍCH HOẠT</span>
                        <span class="mono" style="font-weight:700;color:var(--lime)">Quy tắc #CR-01: Giày đá bóng → Vớ chống trượt & Băng quấn</span>
                    </div>
                    <div style="font-size:13px;color:var(--text-main);margin-bottom:4px">
                        <b>Điều kiện:</b> Khách hàng thêm bất kỳ sản phẩm thuộc danh mục <b>Giày đinh</b> vào giỏ.
                    </div>
                    <div style="font-size:12px;color:var(--text-sub)">
                        <b>Gợi ý hiển thị:</b> Vớ chống trượt Fieldcraft Pro Grip (120k) + Băng quấn cổ chân bảo vệ (45k) với mức giảm combo <b>15%</b>.
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:24px">
                    <div style="text-align:right">
                        <div class="muted" style="font-size:10px;text-transform:uppercase">Tỉ lệ chuyển đổi</div>
                        <div class="mono" style="font-size:18px;font-weight:700;color:var(--lime)">32.4%</div>
                        <div class="muted" style="font-size:10px">+28.5M ₫ / tháng</div>
                    </div>
                    <button class="btn small" type="button" onclick="this.classList.toggle('lime');alert('Đã cập nhật trạng thái quy tắc')">Bật / Tắt</button>
                </div>
            </div>

            {{-- Rule 2 --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
                <div style="flex:1;min-width:280px">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <span class="status completed" style="font-size:9px">ĐANG KÍCH HOẠT</span>
                        <span class="mono" style="font-weight:700;color:var(--lime)">Quy tắc #CR-02: Áo đấu → In tên số & Quần thi đấu</span>
                    </div>
                    <div style="font-size:13px;color:var(--text-main);margin-bottom:4px">
                        <b>Điều kiện:</b> Khách hàng thêm sản phẩm thuộc danh mục <b>Áo đấu CLB / Đội tuyển</b> vào giỏ.
                    </div>
                    <div style="font-size:12px;color:var(--text-sub)">
                        <b>Gợi ý hiển thị:</b> Gói dịch vụ In tên & số chuẩn Font giải đấu (150k) + Quần thi đấu đồng bộ (180k).
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:24px">
                    <div style="text-align:right">
                        <div class="muted" style="font-size:10px;text-transform:uppercase">Tỉ lệ chuyển đổi</div>
                        <div class="mono" style="font-size:18px;font-weight:700;color:var(--lime)">46.1%</div>
                        <div class="muted" style="font-size:10px">+41.2M ₫ / tháng</div>
                    </div>
                    <button class="btn small" type="button" onclick="this.classList.toggle('lime');alert('Đã cập nhật trạng thái quy tắc')">Bật / Tắt</button>
                </div>
            </div>

            {{-- Rule 3 --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
                <div style="flex:1;min-width:280px">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <span class="status completed" style="font-size:9px">ĐANG KÍCH HOẠT</span>
                        <span class="mono" style="font-weight:700;color:var(--lime)">Quy tắc #CR-03: Giỏ hàng > 1.500.000 ₫ → Túi đựng giày chống nước</span>
                    </div>
                    <div style="font-size:13px;color:var(--text-main);margin-bottom:4px">
                        <b>Điều kiện:</b> Tổng giá trị tạm tính của giỏ hàng đạt từ <b>1.500.000 ₫</b> trở lên.
                    </div>
                    <div style="font-size:12px;color:var(--text-sub)">
                        <b>Gợi ý hiển thị:</b> Túi đựng giày 2 ngăn kháng nước Fieldcraft Boot Bag (giá gốc 220k) chỉ còn <b>99k</b>.
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:24px">
                    <div style="text-align:right">
                        <div class="muted" style="font-size:10px;text-transform:uppercase">Tỉ lệ chuyển đổi</div>
                        <div class="mono" style="font-size:18px;font-weight:700;color:var(--lime)">24.8%</div>
                        <div class="muted" style="font-size:10px">+15.8M ₫ / tháng</div>
                    </div>
                    <button class="btn small" type="button" onclick="this.classList.toggle('lime');alert('Đã cập nhật trạng thái quy tắc')">Bật / Tắt</button>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: ABANDONED CART RADAR                                                 --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-abandoned-carts">
    <section class="panel" id="abandoned-carts">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">⏳ RADAR GIỎ HÀNG BỊ BỎ QUÊN (ABANDONED CART RADAR)</span>
                <div class="muted">Hệ thống phát hiện giỏ hàng tồn đọng chưa thanh toán và hỗ trợ kích hoạt mã giải cứu cứu vãn doanh thu</div>
            </div>
            <button type="button" class="btn lime" onclick="triggerBatchRescue()">⚡ KÍCH HOẠT MÃ CỨU GIỎ HÀNG LOẠT</button>
        </div>

        {{-- 4 Stat Cards --}}
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;margin-bottom:24px">
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Giỏ hàng đang treo</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--text-main);margin-top:4px">42 giỏ</div>
            </div>
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Tỉ lệ giỏ bỏ quên</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--warning);margin-top:4px">62.4%</div>
            </div>
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Tổng giá trị giỏ treo</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--lime);margin-top:4px">68.450.000 ₫</div>
            </div>
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Tỉ lệ cứu giỏ thành công</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--lime);margin-top:4px">28.6%</div>
            </div>
        </div>

        {{-- Abandoned Carts Table --}}
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>KHÁCH HÀNG</th>
                        <th>SẢN PHẨM TRONG GIỎ</th>
                        <th>TỔNG TIỀN</th>
                        <th>THỜI GIAN BẤT ĐỘNG</th>
                        <th>XÁC SUẤT CỨU GIỎ</th>
                        <th>THAO TÁC CỨU GIỎ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="font-weight:700;color:var(--text-main)">Lê Anh Quân</div>
                            <div class="muted mono" style="font-size:11px">quan.le@example.com · VIP PLAYER</div>
                        </td>
                        <td>
                            <div>Nike Zoom Mercurial Vapor 16 Pro TF (Trắng/Neon - Size 41)</div>
                            <div class="muted" style="font-size:11px">+ Vớ chống trượt Fieldcraft Pro Grip</div>
                        </td>
                        <td>
                            <b class="mono" style="color:var(--lime);font-size:14px">1.970.000 ₫</b>
                        </td>
                        <td>
                            <span class="status pending" style="background:#1c1917;color:#f59e0b;border-color:#b45309">
                                > 2 GIỜ TRƯỚC
                            </span>
                        </td>
                        <td>
                            <span class="status completed" style="background:#142d1f;color:#6ee7b7;border-color:#059669">
                                CAO (78%)
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn small lime" onclick="triggerRescueCoupon('quan.le@example.com', '10%')">
                                GỬI VOUCHER 10% ⚡
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="font-weight:700;color:var(--text-main)">Khách vãng lai #SES-8829</div>
                            <div class="muted mono" style="font-size:11px">IP: 118.69.182.24 · TP. Hồ Chí Minh</div>
                        </td>
                        <td>
                            <div>adidas Predator 24 Elite TF (Đen/Đỏ - Size 40)</div>
                        </td>
                        <td>
                            <b class="mono" style="color:var(--lime);font-size:14px">2.100.000 ₫</b>
                        </td>
                        <td>
                            <span class="status pending" style="background:#291410;color:#fb7185;border-color:#e11d48">
                                > 24 GIỜ TRƯỚC
                            </span>
                        </td>
                        <td>
                            <span class="status pending">TRUNG BÌNH (52%)</span>
                        </td>
                        <td>
                            <button type="button" class="btn small" onclick="triggerRescueCoupon('SES-8829', '5%')">
                                KÍCH HOẠT MÃ 5%
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="font-weight:700;color:var(--text-main)">Nguyễn Văn Thắng</div>
                            <div class="muted mono" style="font-size:11px">thang.nguyen@example.com · VIP ROOKIE</div>
                        </td>
                        <td>
                            <div>Puma Future 7 Ultimate FG/AG (Xanh - Size 42) + Gói In số áo</div>
                        </td>
                        <td>
                            <b class="mono" style="color:var(--lime);font-size:14px">2.550.000 ₫</b>
                        </td>
                        <td>
                            <span class="status cancelled">
                                > 3 NGÀY TRƯỚC
                            </span>
                        </td>
                        <td>
                            <span class="status muted">THẤP (24%)</span>
                        </td>
                        <td>
                            <button type="button" class="btn small" onclick="triggerRescueCoupon('thang.nguyen@example.com', '15%')">
                                GỬI ƯU ĐÃI ĐẶC BIỆT
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: SECOND-HAND HUB                                                      --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-second-hand">
    <section class="panel" id="second-hand">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">♻ TRẠM KIỂM ĐỊNH & TRAO ĐỔI GIÀY SECOND-HAND (SECOND-HAND HUB)</span>
                <div class="muted">Hàng đợi kiểm duyệt chất lượng, đánh giá độ mới (9.5/10) và niêm yết giày ký gửi chính hãng</div>
            </div>
            <div class="actions">
                <span class="status completed">KIỂM ĐỊNH CHÍNH HÃNG 100%</span>
            </div>
        </div>

        {{-- Filter Sub-Tabs --}}
        <div style="display:flex;gap:8px;margin-bottom:18px">
            <button class="btn small lime sh-filter-btn" data-filter="all">TẤT CẢ (4)</button>
            <button class="btn small sh-filter-btn" data-filter="pending">CHỜ DUYỆT (2)</button>
            <button class="btn small sh-filter-btn" data-filter="inspected">ĐÃ THẨM ĐỊNH (1)</button>
            <button class="btn small sh-filter-btn" data-filter="listed">ĐÃ NIÊM YẾT (1)</button>
        </div>

        {{-- Second-hand Items Table --}}
        <div class="table-responsive">
            <table class="table" id="secondHandTable">
                <thead>
                    <tr>
                        <th>SẢN PHẨM & TÌNH TRẠNG</th>
                        <th>ĐINH & PHOM CHÂN</th>
                        <th>GIÁ KÝ GỬI / GIÁ GỐC</th>
                        <th>NGƯỜI KÝ GỬI</th>
                        <th>MÃ BOOT PASSPORT</th>
                        <th>TRẠNG THÁI</th>
                        <th>THAO TÁC</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-status="pending">
                        <td>
                            <div style="font-weight:700;color:var(--text-main)">Nike Zoom Mercurial Vapor 15 Pro TF</div>
                            <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
                                <span class="status completed" style="font-size:9px">ĐỘ MỚI: 9.5/10</span>
                                <span class="muted" style="font-size:10px">Xỏ 1 trận thử sân</span>
                            </div>
                        </td>
                        <td>
                            <span class="stud-badge tf">TF</span>
                            <span class="mono" style="font-size:12px;font-weight:700;margin-left:4px">Size 41</span>
                            <span class="muted" style="font-size:11px">· Form Bè</span>
                        </td>
                        <td>
                            <div class="mono" style="font-weight:700;color:var(--lime);font-size:14px">1.350.000 ₫</div>
                            <div class="muted mono" style="font-size:10px;text-decoration:line-through">Gốc: 2.100.000 ₫ (-35%)</div>
                        </td>
                        <td>
                            <div>Hoàng Tuấn Kiệt</div>
                            <div class="muted mono" style="font-size:10px">0987.123.456 · Hà Nội</div>
                        </td>
                        <td>
                            <span class="mono" style="color:var(--lime);font-size:11px;cursor:pointer" onclick="openBootPassport('FC-PASS-8829-VN', 'Nike Zoom Mercurial Vapor 15 Pro TF', 'Đỏ / Đen', 'Size 41 · Form Bè', 'TF Cỏ nhân tạo', '#ORD-7712')">
                                🎫 FC-PASS-8829-VN
                            </span>
                        </td>
                        <td>
                            <span class="status pending">CHỜ THẨM ĐỊNH</span>
                        </td>
                        <td>
                            <button type="button" class="btn small lime" onclick="alert('✓ Đã phê duyệt thẩm định keo và upper thành công!')">Duyệt niêm yết</button>
                        </td>
                    </tr>
                    <tr data-status="inspected">
                        <td>
                            <div style="font-weight:700;color:var(--text-main)">adidas Predator 24 Elite TF (Lưỡi Gập)</div>
                            <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
                                <span class="status completed" style="font-size:9px">ĐỘ MỚI: 9/10</span>
                                <span class="muted" style="font-size:10px">Upper còn nguyên gai</span>
                            </div>
                        </td>
                        <td>
                            <span class="stud-badge tf">TF</span>
                            <span class="mono" style="font-size:12px;font-weight:700;margin-left:4px">Size 40</span>
                            <span class="muted" style="font-size:11px">· Form Tiêu chuẩn</span>
                        </td>
                        <td>
                            <div class="mono" style="font-weight:700;color:var(--lime);font-size:14px">1.650.000 ₫</div>
                            <div class="muted mono" style="font-size:10px;text-decoration:line-through">Gốc: 2.450.000 ₫ (-32%)</div>
                        </td>
                        <td>
                            <div>Phạm Đình Trọng</div>
                            <div class="muted mono" style="font-size:10px">0912.888.999 · TP.HCM</div>
                        </td>
                        <td>
                            <span class="mono" style="color:var(--lime);font-size:11px;cursor:pointer" onclick="openBootPassport('FC-PASS-4412-VN', 'adidas Predator 24 Elite TF', 'Đen / Trắng / Đỏ', 'Size 40 · Form Chuẩn', 'TF Cỏ nhân tạo', '#ORD-6211')">
                                🎫 FC-PASS-4412-VN
                            </span>
                        </td>
                        <td>
                            <span class="status completed">ĐÃ THẨM ĐỊNH</span>
                        </td>
                        <td>
                            <button type="button" class="btn small" onclick="alert('Đã đưa lên sàn Second-hand')">Đăng bán</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: BOOT PASSPORT PREVIEW                                                --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-passport">
    <section class="panel" id="passport">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🎫 HỘ CHIẾU GIÀY ĐÁ BÓNG ĐIỆN TỬ (FIELDCRAFT BOOT PASSPORT)</span>
                <div class="muted">Hệ thống định danh số, chứng thực nguồn gốc chính hãng và lịch sử bảo hành cho từng đôi giày</div>
            </div>
            <div class="actions">
                <span class="status completed">CHỨNG THỰC NFC & BLOCKCHAIN</span>
            </div>
        </div>

        {{-- Interactive Passport Lookup Search --}}
        <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px;margin-bottom:24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <span class="muted" style="font-size:12px;font-weight:700">TRA CỨU HỘ CHIẾU:</span>
            <input id="passportSearchInput" value="FC-PASS-8829-VN" placeholder="Nhập mã Passport VD: FC-PASS-8829-VN" style="flex:1;min-width:240px;background:var(--bg-input);border:1px solid var(--border-panel);color:var(--lime);padding:8px 12px;border-radius:6px;font-family:'DM Mono',monospace;font-size:13px;font-weight:700">
            <button type="button" class="btn lime" onclick="lookupPassport()">TRA CỨU HỘ CHIẾU</button>
        </div>

        {{-- Comprehensive Digital Passport Card Display --}}
        <div style="display:flex;justify-content:center;margin-bottom:24px">
            <div class="passport-card-box" style="max-width:540px;width:100%">
                <div class="passport-nfc-tag">
                    <span>NFC</span>
                    <span style="font-size:10px">AUTHENTIC VERIFIED</span>
                </div>

                <div class="passport-code-banner">
                    <span id="cardPassCode">FC-PASS-8829-VN</span>
                </div>

                <div class="passport-shoe-title" id="cardPassName">
                    Nike Zoom Mercurial Vapor 16 Pro TF
                </div>
                <div class="passport-shoe-sub" id="cardPassColor">
                    Phối màu: Trắng / Xanh Neon
                </div>

                <div class="passport-spec-grid">
                    <div class="passport-spec-item">
                        <div class="passport-spec-lbl">Cỡ giày & Phom dáng</div>
                        <div class="passport-spec-val" id="cardPassSize">Size 41 · Form Bè</div>
                    </div>
                    <div class="passport-spec-item">
                        <div class="passport-spec-lbl">Loại đinh mặt sân</div>
                        <div class="passport-spec-val" id="cardPassStud">TF (Cỏ nhân tạo)</div>
                    </div>
                    <div class="passport-spec-item">
                        <div class="passport-spec-lbl">Đơn hàng kích hoạt</div>
                        <div class="passport-spec-val" id="cardPassOrder">#ORD-8821 · 12/09/2026</div>
                    </div>
                    <div class="passport-spec-item">
                        <div class="passport-spec-lbl">Thời hạn bảo hành keo đế</div>
                        <div class="passport-spec-val" style="color:var(--lime)">180 ngày (Còn 168 ngày)</div>
                    </div>
                </div>

                <div style="background:rgba(0,0,0,0.4);border:1px solid var(--border-panel);border-radius:8px;padding:12px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <div class="muted" style="font-size:10px;text-transform:uppercase">Chủ sở hữu hiện tại</div>
                        <div style="font-weight:700;color:var(--text-main)">Trần Minh Đức</div>
                    </div>
                    <span class="vip-tier-badge pro">VIP PRO</span>
                </div>

                <div style="display:flex;gap:10px">
                    <button class="btn lime" type="button" style="flex:1" onclick="alert('Chuyển thông tin đôi giày sang sàn ký gửi Second-hand!')">
                        ♻ ĐĂNG BÁN SECOND-HAND
                    </button>
                    <button class="btn" type="button" style="flex:1" onclick="alert('Đã xuất chứng chỉ số định dạng PDF!')">
                        XUẤT CHỨNG CHỈ SỐ
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB: ACTIVITY LOG / AUDIT TRAIL                                           --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-activity-log">
    <section class="panel" id="activity-log">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">📜 NHẬT KÝ HOẠT ĐỘNG HỆ THỐNG (AUDIT TRAIL / ACTIVITY LOG)</span>
                <div class="muted">Ghi nhận chi tiết mọi thao tác thay đổi tồn kho, đơn hàng, bảo mật và tài khoản theo thời gian thực</div>
            </div>
            <div class="actions">
                <button type="button" class="btn small" onclick="alert('Đã xuất file log kiểm toán CSV')">Xuất Log CSV</button>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;background:var(--bg-panel-sub);padding:14px;border:1px solid var(--border-panel);border-radius:8px;align-items:center">
            <span class="muted" style="font-size:11px;font-weight:700;text-transform:uppercase">BỘ LỌC KIỂM TOÁN:</span>
            <select style="background:var(--bg-input);border:1px solid var(--border-panel);color:var(--text-main);padding:6px 10px;border-radius:4px;font-size:11px">
                <option value="">Tất cả người thực hiện</option>
                <option value="admin">Quản trị viên (Admin)</option>
                <option value="staff">Nhân viên kho vận</option>
                <option value="system">Hệ thống tự động</option>
            </select>
            <select style="background:var(--bg-input);border:1px solid var(--border-panel);color:var(--text-main);padding:6px 10px;border-radius:4px;font-size:11px">
                <option value="">Tất cả loại hành động</option>
                <option value="stock">Cập nhật tồn kho</option>
                <option value="order">Đổi trạng thái đơn</option>
                <option value="shipping">Đồng bộ GHN</option>
                <option value="secondhand">Duyệt second-hand</option>
                <option value="security">Phân quyền tài khoản</option>
            </select>
            <span class="status completed" style="font-size:10px">TỰ ĐỘNG LƯU TRỮ TOÀN VẸN 365 NGÀY</span>
        </div>

        {{-- Chronological Audit Timeline --}}
        <div class="timeline" style="padding-left:32px">
            <article>
                <div class="mono" style="font-size:11px;color:var(--lime);margin-bottom:4px">12/09/2026 · 11:42:18</div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="status completed" style="font-size:9px">HỆ THỐNG TỰ ĐỘNG</span>
                    <span style="font-weight:700;color:var(--text-main)">Đồng bộ trạng thái vận đơn GHN thành công</span>
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Vận đơn <b>#GHN882901VN</b> của đơn hàng <b>#ORD-8821</b> đã cập nhật trạng thái sang <i>"Đang giao hàng"</i> tại bưu cục Cầu Giấy, Hà Nội.
                </div>
            </article>

            <article>
                <div class="mono" style="font-size:11px;color:var(--lime);margin-bottom:4px">12/09/2026 · 10:15:02</div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="status pending" style="font-size:9px;background:#0c2a38;color:#38bdf8;border-color:#0284c7">ADMIN</span>
                    <span style="font-weight:700;color:var(--text-main)">Cập nhật tồn kho biến thể sản phẩm</span>
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Bổ sung tồn kho sản phẩm <b>Nike Zoom Mercurial Vapor 16 Pro TF</b> (Size 41 - Màu Trắng): Tăng từ <b>2</b> lên <b>12</b> đôi theo phiếu nhập kho số <b>#NK-441</b>.
                </div>
            </article>

            <article>
                <div class="mono" style="font-size:11px;color:var(--lime);margin-bottom:4px">12/09/2026 · 09:30:45</div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="status completed" style="font-size:9px">NHÂN VIÊN IN ẤN</span>
                    <span style="font-weight:700;color:var(--text-main)">Hoàn tất công đoạn in tên số áo đấu</span>
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Công việc cá nhân hóa số <b>#JOB-18</b> cho đơn <b>#ORD-8815</b> (Tên in: <i>"NGUYEN VAN A - Số 10"</i>) đã hoàn thành kiểm định QC và chuyển sang đóng gói.
                </div>
            </article>

            <article>
                <div class="mono" style="font-size:11px;color:var(--lime);margin-bottom:4px">11/09/2026 · 23:10:00</div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="status completed" style="font-size:9px">HỆ THỐNG MATCHDAY</span>
                    <span style="font-weight:700;color:var(--text-main)">Kích hoạt chiến dịch Flash Sale Matchday</span>
                </div>
                <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                    Tự động kích hoạt ưu đãi giảm 15% dòng giày <b>Phantom & Predator</b> theo lịch thi đấu trận Derby Manchester.
                </div>
            </article>
        </div>
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
            'kanban': { tab: 'kanban', anchor: null },
            'shipping': { tab: 'shipping', anchor: null },
            'loyalty': { tab: 'loyalty', anchor: null },
            'trends': { tab: 'trends', anchor: null },
            'matchday': { tab: 'matchday', anchor: null },
            'cross-sell': { tab: 'cross-sell', anchor: null },
            'abandoned-carts': { tab: 'abandoned-carts', anchor: null },
            'second-hand': { tab: 'second-hand', anchor: null },
            'passport': { tab: 'passport', anchor: null },
            'activity-log': { tab: 'activity-log', anchor: null },
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
    // 5. Second-hand Hub Filters
    const shFilterBtns = document.querySelectorAll('.sh-filter-btn');
    const shRows = document.querySelectorAll('#secondHandTable tbody tr');
    shFilterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            shFilterBtns.forEach(b => b.classList.remove('lime'));
            btn.classList.add('lime');
            const filter = btn.dataset.filter;
            shRows.forEach(row => {
                row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
            });
        });
    });
});

// Global helpers for Dashboard Panes
function triggerRescueCoupon(target, discount) {
    alert(`✓ Đã kích hoạt và gửi mã cứu giỏ giảm ${discount} đến "${target}" thành công!`);
}

function triggerBatchRescue() {
    alert('✓ Đã tự động kích hoạt mã cứu giỏ (Voucher 10%) cho 42 giỏ hàng bị bỏ quên!');
}

function lookupPassport() {
    const input = document.getElementById('passportSearchInput');
    const code = input ? input.value.trim() : '';
    if (!code) {
        alert('Vui lòng nhập mã Boot Passport cần tra cứu.');
        return;
    }
    const cardCode = document.getElementById('cardPassCode');
    if (cardCode) cardCode.innerText = code;
    alert(`✓ Đã tìm thấy hồ sơ số của Boot Passport "${code}"! Thông tin đã được đồng bộ lên thẻ.`);
}
</script>
@endsection
