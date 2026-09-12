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
    $supportTicketsAll = \App\Models\SupportTicket::with(['user', 'order', 'messages.sender'])->latest('last_message_at')->take(50)->get();
    $openSupportTicketsCount = \App\Models\SupportTicket::whereIn('status', ['open', 'in_progress'])->count();

    // Real Order Kanban data
    $kanbanOrders = \App\Models\Order::with(['user', 'items.variant.product'])->latest()->take(50)->get();
    $kanbanPending = $kanbanOrders->where('status', 'pending');
    $kanbanConfirmed = $kanbanOrders->where('status', 'confirmed');
    $kanbanPacking = $kanbanOrders->filter(fn($o) => in_array($o->status, ['packing', 'preparing']));
    $kanbanShipping = $kanbanOrders->where('status', 'shipping');
    $kanbanCompleted = $kanbanOrders->where('status', 'completed');

    // Real Shipping Hub data
    $shippingSummary = app(\App\Services\ShippingHubService::class)->summary();
    $shippingWaybills = \App\Models\Order::query()
        ->whereNotNull('ghn_order_code')
        ->with('user')
        ->latest()
        ->take(20)
        ->get();

    // Real Loyalty & VIP Members
    $loyaltyService = app(\App\Services\LoyaltyService::class);
    $customerSegmentService = app(\App\Services\CustomerSegmentService::class);
    $realVipCustomers = $loyaltyService->customerMetrics()
        ->orderByDesc('completed_spend')
        ->take(20)
        ->get()
        ->map(function ($c) use ($loyaltyService, $customerSegmentService) {
            $profile = $loyaltyService->profileFromMetrics((array) $c->getAttributes());
            $segments = $customerSegmentService->fromMetrics((array) $c->getAttributes(), $profile['tier'] ?? 'ROOKIE');
            return [
                'user' => $c,
                'profile' => $profile,
                'segments' => $segments,
            ];
        });

    // Real Football Trends & Provider adapter
    $footballApi = app(\App\Services\FootballApiAdapter::class);
    $realTrends = \App\Models\FootballTrend::with('creator')->latest()->get();

    // Real Matchday Campaigns
    $realCampaigns = \App\Models\MatchdayCampaign::with(['teamProfile', 'coupon', 'products', 'approver'])->latest()->get();

    // Real Cross-sell Rules
    $realCrossSellRules = \App\Models\CrossSellRule::with('recommendedProduct')->latest()->get();

    // Real Abandoned Carts
    $realAbandonedCarts = app(\App\Services\AbandonedCartService::class)->carts();

    // Real Second-hand Listings
    $realSecondHandListings = \App\Models\SecondHandListing::with(['user', 'reviewer'])->latest()->get();

    // Real Boot Passports
    $realBootPassports = \App\Models\BootPassport::with(['user', 'order', 'variant.product'])->latest()->take(25)->get();

    // Real Activity Logs
    $realActivityLogs = \App\Models\ActivityLog::with(['actor', 'subject'])->latest()->take(30)->get();

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
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

    /* ── Kanban Board Styles ── */
    .kanban-board {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 14px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: var(--border-panel) transparent;
    }
    .kanban-column {
        flex: 0 0 290px;
        min-width: 290px;
        background: var(--bg-panel);
        border: 1px solid var(--border-panel);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        max-height: 850px;
    }
    .kanban-col-head {
        padding: 12px 14px;
        border-bottom: 1px solid var(--border-panel);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-panel-sub);
        border-radius: 8px 8px 0 0;
    }
    .kanban-col-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font: 700 13px/1 'Oswald', sans-serif;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-main);
    }
    .kanban-col-count {
        font: 700 11px/1 'DM Mono', monospace;
        background: var(--bg-card);
        border: 1px solid var(--border-panel);
        padding: 2px 7px;
        border-radius: 10px;
        color: var(--text-muted);
    }
    .kanban-cards-list {
        padding: 12px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }
    .kanban-card {
        background: var(--bg-panel-sub);
        border: 1px solid var(--border-panel);
        border-radius: 6px;
        padding: 12px;
        cursor: pointer;
        transition: all .15s ease;
    }
    .kanban-card:hover {
        border-color: var(--lime);
        transform: translateY(-2px);
    }
    .kanban-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .kanban-card-id {
        font: 700 12px/1 'DM Mono', monospace;
        color: var(--lime);
    }
    .kanban-health-pill {
        font: 700 9px/1 'DM Mono', monospace;
        padding: 2px 6px;
        border-radius: 3px;
        letter-spacing: .04em;
    }
    .kanban-health-pill.healthy {
        background: var(--success-bg);
        color: var(--lime);
        border: 1px solid var(--success-border);
    }
    .kanban-health-pill.warning {
        background: var(--warning-bg);
        color: var(--warning);
        border: 1px solid var(--warning-border);
    }
    .kanban-health-pill.critical {
        background: var(--danger-bg);
        color: var(--danger);
        border: 1px solid var(--danger-border);
    }
    .kanban-card-customer {
        font-weight: 700;
        color: var(--text-main);
        font-size: 13px;
        margin-bottom: 2px;
    }
    .kanban-card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 6px;
    }
    .kanban-card-total {
        font: 700 13px/1 'DM Mono', monospace;
        color: var(--lime);
    }

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
        <span>▥ Kanban đơn hàng</span>
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
        <span>🛡 Đội bóng</span>
    </button>
    <button class="dash-tab-btn" data-tab="customization">
        <span>🎽 Xưởng in & Cá nhân hóa</span>
        @if($customizationJobs->where('status', '!=', 'completed')->count() > 0)
            <span class="tab-badge" style="background:var(--warning);color:#07110d">{{ $customizationJobs->where('status', '!=', 'completed')->count() }}</span>
        @endif
    </button>
    <button class="dash-tab-btn" data-tab="support">
        <span>🎧 Hỗ trợ khách hàng</span>
        @if($openSupportTicketsCount > 0)
            <span class="tab-badge" style="background:var(--info);color:#07110d">{{ $openSupportTicketsCount }}</span>
        @endif
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
        <span>🛒 Bán chéo sản phẩm</span>
    </button>
    <button class="dash-tab-btn" data-tab="abandoned-carts">
        <span>⏳ Giỏ hàng bị bỏ quên</span>
    </button>
    <button class="dash-tab-btn" data-tab="second-hand">
        <span>♻ Trạm Second-hand</span>
    </button>
    <button class="dash-tab-btn" data-tab="passport">
        <span>🎫 Hộ chiếu sản phẩm</span>
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
                    'refund_required' => 'Cần hoàn tiền',
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
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:20px;">
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
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:14px" id="paymentBreakdownCards">
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
                <div class="muted" style="font-size:11px;margin-top:4px">
                    Đã hoàn: <b class="mono" style="color:var(--text-main)">{{ number_format($financeData['refunded_amount']) }} ₫</b>
                    @if(($financeData['refund_required_count'] ?? 0) > 0)
                        · Cần hoàn: <b class="mono" style="color:var(--warning)">{{ number_format($financeData['refund_required_amount'] ?? 0) }} ₫</b> ({{ $financeData['refund_required_count'] }})
                    @endif
                </div>
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
</div>

{{-- ========================================================================= --}}
{{-- TAB: XƯỞNG IN & CÁ NHÂN HÓA (CUSTOMIZATION WORKSHOP)                       --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-customization">
    <section class="panel" id="customization">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🎽 XƯỞNG IN & CÁ NHÂN HÓA (CUSTOMIZATION WORKSHOP)</span>
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

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:6px;padding:12px;margin-bottom:14px">
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
{{-- TAB: HỖ TRỢ KHÁCH HÀNG (CUSTOMER SUPPORT HUB)                             --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-support">
    <section class="panel" id="support">
        <div class="toolbar" style="margin-bottom:16px">
            <div>
                <span class="toolbar-title">🎧 TRUNG TÂM HỖ TRỢ KHÁCH HÀNG (CUSTOMER SUPPORT HUB)</span>
                <div class="muted">Xử lý yêu cầu hỗ trợ, khiếu nại đơn hàng, vận chuyển và giải đáp thắc mắc cho khách hàng</div>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <span class="status {{ $openSupportTicketsCount > 0 ? 'pending' : 'completed' }}">
                    {{ $openSupportTicketsCount }} yêu cầu chờ xử lý
                </span>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start">
            {{-- Left Column: Ticket List & Filter --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:10px;padding:16px;display:flex;flex-direction:column;gap:12px;height:720px">
                {{-- Status Filter Buttons --}}
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <button type="button" class="btn small support-filter-btn active" data-filter="all" onclick="filterSupportTickets('all')">
                        Tất cả ({{ $supportTicketsAll->count() }})
                    </button>
                    <button type="button" class="btn small support-filter-btn" data-filter="open" onclick="filterSupportTickets('open')">
                        Mới mở ({{ $supportTicketsAll->where('status', 'open')->count() }})
                    </button>
                    <button type="button" class="btn small support-filter-btn" data-filter="in_progress" onclick="filterSupportTickets('in_progress')">
                        Đang xử lý ({{ $supportTicketsAll->where('status', 'in_progress')->count() }})
                    </button>
                    <button type="button" class="btn small support-filter-btn" data-filter="resolved" onclick="filterSupportTickets('resolved')">
                        Đã giải quyết ({{ $supportTicketsAll->where('status', 'resolved')->count() }})
                    </button>
                    <button type="button" class="btn small support-filter-btn" data-filter="closed" onclick="filterSupportTickets('closed')">
                        Đã đóng ({{ $supportTicketsAll->where('status', 'closed')->count() }})
                    </button>
                </div>

                {{-- Search Box --}}
                <div style="position:relative">
                    <input type="text" id="supportSearchInput" placeholder="Tìm kiếm theo mã, khách, tiêu đề..." oninput="searchSupportTickets(this.value)" class="input" style="width:100%;font-size:12px;padding:8px 12px;background:var(--bg-panel);border:1px solid var(--border-panel);color:var(--text-main);border-radius:6px">
                </div>

                {{-- Scrollable Ticket Items --}}
                <div id="supportTicketsList" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding-right:4px">
                    @forelse($supportTicketsAll as $st)
                        @php
                            $catLabel = match($st->category) {
                                'order' => 'Đơn hàng',
                                'payment' => 'Thanh toán',
                                'shipping' => 'Vận chuyển',
                                'product' => 'Sản phẩm',
                                'refund' => 'Hoàn tiền',
                                'account' => 'Tài khoản',
                                default => 'Khác'
                            };
                            $statusLabel = match($st->status) {
                                'open' => 'Mới mở',
                                'in_progress' => 'Đang xử lý',
                                'resolved' => 'Đã giải quyết',
                                'closed' => 'Đã đóng',
                                default => $st->status
                            };
                            $statusClass = match($st->status) {
                                'open' => 'pending',
                                'in_progress' => 'shipping',
                                'resolved' => 'completed',
                                'closed' => 'cancelled',
                                default => 'muted'
                            };
                        @endphp
                        <div class="support-ticket-item {{ $loop->first ? 'active' : '' }}"
                             data-id="{{ $st->id }}"
                             data-status="{{ $st->status }}"
                             data-subject="{{ strtolower($st->subject) }}"
                             data-user="{{ strtolower($st->user?->name . ' ' . $st->user?->email) }}"
                             data-order="{{ strtolower($st->order?->number ?? '') }}"
                             onclick="selectSupportTicket({{ $st->id }})"
                             style="background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:8px;padding:12px;cursor:pointer;transition:all .15s ease">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                                <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700">#{{ $st->id }} · {{ $catLabel }}</span>
                                <span class="status {{ $statusClass }}" style="font-size:9px;padding:2px 6px">{{ $statusLabel }}</span>
                            </div>
                            <div style="font-weight:700;font-size:13px;color:var(--text-main);margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $st->subject }}
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted)">
                                <span>👤 {{ $st->user?->name }}</span>
                                <span>{{ $st->last_message_at?->diffForHumans() ?? $st->created_at->diffForHumans() }}</span>
                            </div>
                            @if($st->order)
                                <div class="mono" style="font-size:10px;color:var(--lime);margin-top:4px">
                                    Đơn: #{{ $st->order->number }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="muted" style="text-align:center;padding:40px 10px;font-size:12px">
                            Không có yêu cầu hỗ trợ nào.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Right Column: Conversation Thread & Action Hub --}}
            <div id="supportDetailPanel" style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:10px;padding:20px;display:flex;flex-direction:column;height:720px">
                @if($supportTicketsAll->isNotEmpty())
                    @php $firstTicket = $supportTicketsAll->first(); @endphp
                    {{-- Detail Header --}}
                    <div id="ticketDetailHeader" style="border-bottom:1px solid var(--border-panel);padding-bottom:16px;margin-bottom:16px">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
                            <div>
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                                    <span class="mono" id="ticketDetailId" style="font-size:12px;color:var(--lime);font-weight:700">#{{ $firstTicket->id }}</span>
                                    <h3 id="ticketDetailSubject" style="font:700 18px/1.2 'Oswald',sans-serif;color:var(--text-main);margin:0">
                                        {{ $firstTicket->subject }}
                                    </h3>
                                    <span id="ticketDetailStatusBadge" class="status completed">{{ $firstTicket->status }}</span>
                                </div>
                                <div class="muted" style="font-size:12px;display:flex;gap:14px;flex-wrap:wrap">
                                    <span>Danh mục: <b id="ticketDetailCategory" style="color:var(--text-sub)">{{ $firstTicket->category }}</b></span>
                                    <span>Khách hàng: <a id="ticketDetailCustomerLink" href="{{ route('admin.customers.show', $firstTicket->user_id) }}" class="lime-link" target="_blank">{{ $firstTicket->user?->name }} ({{ $firstTicket->user?->email }})</a></span>
                                    <span id="ticketDetailOrderWrap">
                                        @if($firstTicket->order)
                                            Đơn: <a id="ticketDetailOrderLink" href="{{ route('admin.orders.show', $firstTicket->order_id) }}" class="lime-link mono" target="_blank">#{{ $firstTicket->order->number }}</a>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Status Transition Form --}}
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="muted" style="font-size:11px">Trạng thái:</span>
                                <select id="ticketStatusSelect" onchange="changeTicketStatus(this.value)" class="input" style="font-size:12px;padding:6px 10px;background:var(--bg-panel);border:1px solid var(--border-panel);color:var(--text-main);border-radius:6px">
                                    <option value="open">Mới mở (open)</option>
                                    <option value="in_progress">Đang xử lý (in_progress)</option>
                                    <option value="resolved">Đã giải quyết (resolved)</option>
                                    <option value="closed">Đã đóng (closed)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Scrollable Messages Thread --}}
                    <div id="ticketMessagesThread" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:10px 4px;margin-bottom:16px">
                        {{-- Filled by JS --}}
                    </div>

                    {{-- Reply Box --}}
                    <div id="ticketReplyBox" style="border-top:1px solid var(--border-panel);padding-top:14px">
                        <form id="supportReplyForm" onsubmit="submitSupportReply(event)">
                            @csrf
                            <div style="display:flex;gap:10px">
                                <textarea id="supportReplyMessage" name="message" required rows="3" placeholder="Nhập nội dung phản hồi chính thức từ Fieldcraft Support..." style="flex:1;background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:6px;padding:10px;color:var(--text-main);font-size:13px;resize:none"></textarea>
                                <button type="submit" id="btnSendSupportReply" class="btn lime" style="align-self:flex-end;height:42px;white-space:nowrap">
                                    GỬI PHẢN HỒI ↵
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div style="text-align:center;padding:60px 20px" class="muted">
                        Chưa có yêu cầu hỗ trợ nào từ khách hàng.
                    </div>
                @endif
            </div>
        </div>
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
            $nextStatusMap = [
                'pending' => ['status' => 'confirmed', 'label' => 'Xác nhận đơn →'],
                'confirmed' => ['status' => 'packing', 'label' => 'Đóng gói →'],
                'packing' => ['status' => 'shipping', 'label' => 'Bàn giao GHN →'],
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
                                $nextAction = $nextStatusMap[$col['id']] ?? null;
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
                                    @elseif($order->payment_status === 'refund_required')
                                        <span class="status cancelled" style="font-size:9px;padding:2px 6px">CẦN HOÀN TIỀN</span>
                                    @elseif($order->payment_status === 'refund_pending')
                                        <span class="status pending" style="font-size:9px;padding:2px 6px">ĐANG XỬ LÝ HOÀN TIỀN</span>
                                    @elseif($order->payment_status === 'refunded')
                                        <span class="status completed" style="font-size:9px;padding:2px 6px">ĐÃ HOÀN TIỀN</span>
                                    @elseif($order->payment_status === 'refund_failed')
                                        <span class="status cancelled" style="font-size:9px;padding:2px 6px">HOÀN TIỀN THẤT BẠI</span>
                                    @else
                                        <span class="status pending" style="font-size:9px;padding:2px 6px">CHƯA THANH TOÁN</span>
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

                                @if($nextAction)
                                    <div style="margin-top:10px;padding-top:6px;border-top:1px solid rgba(255,255,255,0.05);display:flex;justify-content:flex-end">
                                        <button type="button" class="btn small lime" style="font-size:10px;padding:4px 8px" onclick="event.stopPropagation(); updateOrderStatus({{ $order->id }}, '{{ $nextAction['status'] }}')">
                                            {{ $nextAction['label'] }}
                                        </button>
                                    </div>
                                @elseif($col['id'] === 'shipping')
                                    <div style="margin-top:10px;padding-top:6px;border-top:1px solid rgba(255,255,255,0.05);display:flex;justify-content:flex-end;gap:6px;flex-wrap:wrap">
                                        @if($order->ghn_order_code)
                                            <button type="button" class="btn small" style="font-size:10px;padding:4px 8px;background:var(--bg-panel-sub);border:1px solid var(--lime);color:var(--lime)" onclick="event.stopPropagation(); syncGhnOrder({{ $order->id }})">
                                                Đồng bộ GHN ↻
                                            </button>
                                        @endif
                                        @if(app()->environment(['local', 'testing']) && Route::has('admin.orders.manual-complete'))
                                            <button type="button" class="btn small lime" style="font-size:10px;padding:4px 8px" onclick="event.stopPropagation(); manualCompleteOrder({{ $order->id }})">
                                                Hoàn tất đơn ✓
                                            </button>
                                        @endif
                                    </div>
                                @endif
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
                @if($shippingSummary['connected'] ?? false)
                    <span class="status completed">GHN: ĐÃ KẾT NỐI</span>
                @else
                    <span class="status muted">GHN: CHƯA KẾT NỐI</span>
                @endif
            </div>
        </div>

        {{-- Carrier Integration Status Cards --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;margin-bottom:24px">
            @php
                $providerInfo = [
                    'ghn' => [
                        'title' => 'GIAO HÀNG NHANH (GHN)',
                        'sub' => 'Tích hợp API v2 chuẩn thương mại điện tử',
                        'desc' => 'Tự động tính cước theo trọng lượng/kích thước và sinh mã vận đơn khi đơn thanh toán.',
                        'meta_label' => 'ShopID',
                        'meta_val' => config('services.ghn.shop_id') ?: '—',
                    ],
                    'spx' => [
                        'title' => 'SPX EXPRESS',
                        'sub' => 'Shopee Xpress B2C',
                        'desc' => 'Dịch vụ vận chuyển đối tác SPX cho mạng lưới thương mại điện tử.',
                        'meta_label' => 'Cấu hình API',
                        'meta_val' => 'Chưa cấu hình API Key',
                    ],
                    'grab_express' => [
                        'title' => 'GRABEXPRESS SIÊU TỐC',
                        'sub' => 'Giao hỏa tốc 2 giờ nội thành',
                        'desc' => 'Dịch vụ giao hàng tức thì bằng xe máy nội đô trong 2 giờ.',
                        'meta_label' => 'Dịch vụ nội đô',
                        'meta_val' => 'Chưa liên kết tài khoản',
                    ],
                    'grab_bike' => [
                        'title' => 'GRABBIKE GIAO HỎA TỐC',
                        'sub' => 'Giao chặng ngắn theo yêu cầu',
                        'desc' => 'Vận chuyển linh hoạt tức thời cho các đơn hàng khẩn cấp nội thành.',
                        'meta_label' => 'Dịch vụ chặng ngắn',
                        'meta_val' => 'Chưa kết nối',
                    ],
                ];
                $providersList = $shippingSummary['providers'] ?? [];
            @endphp

            @forelse($providersList as $prov)
                @php
                    $pName = $prov['name'] ?? '';
                    $pConnected = !empty($prov['connected']);
                    $pInfo = $providerInfo[$pName] ?? [
                        'title' => strtoupper(str_replace('_', ' ', $pName)),
                        'sub' => 'Đối tác vận chuyển',
                        'desc' => 'Dịch vụ kết nối đối tác giao nhận.',
                        'meta_label' => 'Trạng thái',
                        'meta_val' => $pConnected ? 'Hoạt động' : 'Chưa kết nối',
                    ];
                @endphp
                <div style="background:var(--bg-panel-sub);border:1px solid {{ $pConnected ? 'var(--lime)' : 'var(--border-panel)' }};border-radius:8px;padding:18px;display:flex;flex-direction:column;justify-content:space-between">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;gap:8px">
                            <div>
                                <div style="font:700 16px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:4px">{{ $pInfo['title'] }}</div>
                                <div class="muted" style="font-size:11px">{{ $pInfo['sub'] }}</div>
                            </div>
                            @if($pConnected)
                                <span class="status completed" style="background:var(--success-bg);color:var(--lime);border:1px solid var(--success-border);white-space:nowrap">
                                    ✓ ĐÃ KẾT NỐI
                                </span>
                            @else
                                <span class="status muted" style="white-space:nowrap">CHƯA KẾT NỐI</span>
                            @endif
                        </div>
                        <div style="font-size:12px;color:var(--text-sub);line-height:1.5;margin-bottom:12px">
                            {{ $pInfo['desc'] }}
                        </div>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border-panel);font-size:11px">
                        <span class="muted">{{ $pInfo['meta_label'] }}:</span>
                        <span class="mono" style="color:var(--text-main)">{{ $pInfo['meta_val'] }}</span>
                    </div>
                </div>
            @empty
                <div style="grid-column:1/-1;text-align:center;padding:24px;border:1px dashed var(--border-panel);border-radius:8px;color:var(--text-muted)">
                    Chưa có cấu hình nhà cung cấp vận chuyển nào trong hệ thống.
                </div>
            @endforelse
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
                                    <b class="mono" style="color:var(--lime)">{{ $sOrder->ghn_order_code }}</b>
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
                                    Chưa có vận đơn GHN nào đang lưu thông.
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
                        <span class="mono muted" style="font-size:11px">MỨC BẮT ĐẦU: 0 ₫</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Tân binh sân cỏ</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Tích điểm: <b>10.000 ₫ = 1 điểm</b></li>
                        <li>Được hưởng chính sách đổi trả tiêu chuẩn</li>
                        <li>Tham gia hệ thống bảo hành Fieldcraft</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Hạng thành viên mặc định khi tạo tài khoản
                </div>
            </div>

            {{-- PLAYER --}}
            <div style="background:var(--bg-panel-sub);border:1px solid #1e3a8a;border-radius:10px;padding:20px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="vip-tier-badge player">PLAYER</span>
                        <span class="mono muted" style="font-size:11px">CHI TIÊU TỪ 5.000.000 ₫</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Cầu thủ năng động</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Ưu đãi thành viên tích lũy điểm thưởng</li>
                        <li>Được hỗ trợ ưu tiên đổi size giày</li>
                        <li>Nhận thông báo sớm các đợt flash sale</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Tự động thăng hạng khi hoàn tất chi tiêu đạt mốc
                </div>
            </div>

            {{-- PRO --}}
            <div style="background:var(--bg-panel-sub);border:1px solid #7c3aed;border-radius:10px;padding:20px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="vip-tier-badge pro">PRO</span>
                        <span class="mono muted" style="font-size:11px">CHI TIÊU TỪ 15.000.000 ₫</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Cầu thủ chuyên nghiệp</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Ưu tiên xử lý đơn hàng cá nhân hóa in tên số</li>
                        <li>Đặc quyền gửi bán trên sàn Second-hand Hub</li>
                        <li>Hỗ trợ tư vấn form chân và chọn đinh chuyên sâu</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--text-muted)">
                    Phân khúc khách hàng thân thiết VIP Pro
                </div>
            </div>

            {{-- FIELDCRAFT ELITE --}}
            <div style="background:linear-gradient(135deg, rgba(202,255,57,0.08), var(--bg-panel-sub));border:1px solid var(--lime);border-radius:10px;padding:20px;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="vip-tier-badge elite">FIELDCRAFT ELITE</span>
                        <span class="mono" style="font-size:11px;color:var(--lime)">CHI TIÊU TỪ 30.000.000 ₫</span>
                    </div>
                    <div style="font:700 20px/1 'Oswald',sans-serif;color:var(--text-main);margin-bottom:8px">Hạng tinh hoa VIP</div>
                    <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--text-sub);line-height:1.6">
                        <li>Quyền ưu tiên đặt trước giày phiên bản giới hạn</li>
                        <li>Cấp Boot Passport chứng nhận quyền lợi đặc biệt</li>
                        <li>Kênh hỗ trợ chăm sóc khách hàng chuyên biệt</li>
                    </ul>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-panel);font-size:11px;color:var(--lime)">
                    ★ Cấp bậc cao nhất trong hệ sinh thái Fieldcraft
                </div>
            </div>
        </div>

        {{-- Top VIP Customers List --}}
        <div>
            <h3 style="font:700 14px/1 'Oswald',sans-serif;letter-spacing:.04em;color:var(--text-main);text-transform:uppercase;margin-bottom:12px">
                BẢNG XẾP HẠNG THÀNH VIÊN THEO CHI TIÊU THỰC
            </h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>KHÁCH HÀNG</th>
                            <th>HẠNG HIỆN TẠI</th>
                            <th>ĐIỂM TÍCH LŨY</th>
                            <th>DOANH SỐ HOÀN TẤT & TIẾN ĐỘ</th>
                            <th>ĐƠN HOÀN TẤT</th>
                            <th>PHÂN KHÚC KHÁCH HÀNG</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($realVipCustomers as $row)
                            @php
                                $u = $row['user'];
                                $p = $row['profile'];
                                $segs = $row['segments'];
                                $tierClass = strtolower(str_replace(' ', '-', $p['tier']));
                            @endphp
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        @if($u->avatar)
                                            <img src="{{ asset('storage/'.$u->avatar) }}" alt="{{ $u->name }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--lime)">
                                        @else
                                            <div style="width:36px;height:36px;border-radius:50%;background:#132a1e;border:1px solid var(--lime);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:900;color:var(--lime)">
                                                {{ strtoupper(mb_substr($u->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('admin.customers.show', $u) }}" class="lime-link" style="font-weight:700">
                                                {{ $u->name }}
                                            </a>
                                            <div class="muted mono" style="font-size:11px">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="vip-tier-badge {{ $tierClass }}">{{ $p['tier'] }}</span>
                                </td>
                                <td>
                                    <b class="mono" style="color:var(--lime);font-size:14px">{{ number_format($p['loyalty_points']) }}</b>
                                    <span class="muted" style="font-size:10px">pts</span>
                                </td>
                                <td style="width:230px">
                                    <div style="display:flex;justify-content:space-between;font-size:10px;margin-bottom:3px">
                                        <span class="muted">{{ number_format($p['completed_spend']) }} ₫ {{ $p['next_threshold'] ? '/ ' . number_format($p['next_threshold']) . ' ₫' : '(Đạt tối đa)' }}</span>
                                        <span class="mono" style="color:var(--lime)">{{ $p['progress_percent'] }}%</span>
                                    </div>
                                    <div style="height:6px;background:var(--bg-panel-sub);border-radius:3px;overflow:hidden;border:1px solid var(--border-panel)">
                                        <div style="height:100%;width:{{ $p['progress_percent'] }}%;background:var(--lime)"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="mono">{{ $p['completed_order_count'] }} đơn</span>
                                </td>
                                <td>
                                    @if(!empty($segs))
                                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                                            @foreach($segs as $seg)
                                                <span class="status muted" style="font-size:9px;padding:2px 6px">{{ $seg }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="muted" style="font-size:11px">—</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    <div style="display:flex;gap:6px;align-items:center">
                                        <a class="btn small" href="{{ route('admin.customers.show', $u) }}">Hồ sơ 360°</a>
                                        <button type="button" class="btn small lime" onclick="openAdminLoyaltyVoucherModal({{ $u->id }}, '{{ addslashes($u->name) }}')">🎁 Tặng voucher</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted" style="text-align:center;padding:24px">Chưa có dữ liệu thành viên khách hàng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- MODAL: TẶNG VOUCHER CHO KHÁCH HÀNG TỪ LOYALTY BOARD --}}
    <div id="adminLoyaltyVoucherModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;padding:20px">
        <div style="background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:12px;max-width:540px;width:100%;padding:24px;position:relative">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid var(--border-panel);padding-bottom:12px">
                <h3 style="font:700 18px/1 'Oswald',sans-serif;color:var(--text-main);margin:0" id="adminLoyaltyVoucherTitle">
                    🎁 TẶNG VOUCHER CHO THÀNH VIÊN
                </h3>
                <button type="button" onclick="closeAdminLoyaltyVoucherModal()" style="background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer">✕</button>
            </div>

            <form id="adminLoyaltyVoucherForm" onsubmit="submitAdminLoyaltyVoucher(event)">
                @csrf
                <input type="hidden" id="adminLoyaltyVoucherUserId" name="user_id">
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
                    <input type="text" name="admin_note" placeholder="VD: Tri ân khách hàng thân thiết" class="input" style="width:100%">
                </div>

                <div id="adminLoyaltyVoucherError" class="errors" style="display:none;margin-bottom:12px"></div>

                <div style="display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn" onclick="closeAdminLoyaltyVoucherModal()">HỦY</button>
                    <button type="submit" id="btnSubmitAdminLoyaltyVoucher" class="btn lime">XÁC NHẬN CẤP VOUCHER</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- TAB: FOOTBALL TREND RADAR                                                 --}}
{{-- ========================================================================= --}}
<div class="dash-tab-pane" id="tab-trends">
    <section class="panel" id="trends">
        <div class="toolbar">
            <div>
                <span class="toolbar-title">🔥 RADAR XU HƯỚNG BÓNG ĐÁ (FOOTBALL TREND RADAR)</span>
                <div class="muted">Phân tích hành vi chọn giày, đặc tính mặt sân và tư vấn phom chân thực tế tại hệ thống Fieldcraft</div>
            </div>
            <div class="actions">
                @if($footballApi->connected())
                    <span class="status completed">{{ $footballApi->name() }}</span>
                @else
                    <span class="status muted">CHƯA KẾT NỐI</span>
                @endif
            </div>
        </div>

        {{-- Data Origin Transparency Notice --}}
        <div class="notice" style="background:#071c12;border-color:var(--border-sub);margin-bottom:20px">
            <span>ℹ</span>
            <div style="font-size:12px;color:var(--text-sub);line-height:1.5">
                <b style="color:var(--lime)">NGUỒN DỮ LIỆU:</b> Toàn bộ xu hướng đang được thu thập <b style="color:#38bdf8">[THỦ CÔNG]</b> từ đội ngũ tư vấn giày chuyên môn tại cửa hàng Fieldcraft.
                @if(!$footballApi->connected())
                    <i>(Dịch vụ dữ liệu đối tác: <b style="color:var(--text-muted)">CHƯA KẾT NỐI</b> — hệ thống cam kết không sử dụng dữ liệu cào giả lập).</i>
                @endif
            </div>
        </div>

        {{-- Real Trends Stream --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:16px;margin-bottom:28px">
            @forelse($realTrends as $trend)
                <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="status {{ $trend->source === 'manual' ? 'pending' : 'completed' }}" style="font-size:9px;padding:2px 6px">
                            {{ $trend->source === 'manual' ? 'THỦ CÔNG' : 'NGOẠI VI' }}
                        </span>
                        <span class="mono muted" style="font-size:10px">{{ $trend->created_at ? $trend->created_at->format('d/m/Y') : '' }}</span>
                    </div>
                    <div style="font:700 16px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                        {{ $trend->title }}
                    </div>
                    @if($trend->campaign_suggestion)
                        <div style="font-size:12px;color:var(--text-sub);line-height:1.4;margin-bottom:8px">
                            <b style="color:var(--lime)">Gợi ý:</b> {{ $trend->campaign_suggestion }}
                        </div>
                    @endif
                    @if(!empty($trend->trend_data))
                        <div class="mono muted" style="font-size:11px;background:rgba(0,0,0,0.2);padding:6px 8px;border-radius:4px">
                            @foreach((array)$trend->trend_data as $k => $v)
                                <span>{{ $k }}: {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}</span>
                                @if(!$loop->last) · @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div style="grid-column:1/-1;padding:32px 18px;text-align:center;border:1px dashed var(--border-panel);border-radius:8px;color:var(--text-muted);font-size:12px">
                    Chưa có dữ liệu xu hướng bóng đá.
                </div>
            @endforelse
        </div>

        {{-- 2 Knowledge Guides: Stud Types & Foot Shapes --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px">
            {{-- Stud Guide --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <b style="font:700 14px/1 'Oswald',sans-serif;color:var(--text-main);text-transform:uppercase">
                        HƯỚNG DẪN MẶT ĐẾ & LOẠI ĐINH THI ĐẤU
                    </b>
                    <span class="status muted" style="font-size:9px">[DANH MỤC FIELDCRAFT]</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;font-size:12px">
                    <div style="padding:10px;border-left:3px solid var(--lime);background:var(--bg-panel)">
                        <div><b style="color:var(--lime)">TF (Turf)</b>: Đinh dăm cao su cho sân cỏ nhân tạo phong trào 5-7 người tại Việt Nam.</div>
                    </div>
                    <div style="padding:10px;border-left:3px solid #60a5fa;background:var(--bg-panel)">
                        <div><b style="color:#60a5fa">FG (Firm Ground)</b>: Đinh cao đúc liền dành riêng cho mặt cỏ tự nhiên tiêu chuẩn 11 người.</div>
                    </div>
                    <div style="padding:10px;border-left:3px solid #c084fc;background:var(--bg-panel)">
                        <div><b style="color:#c084fc">AG (Artificial Grass)</b>: Đinh tròn sợi dài cho sân cỏ nhân tạo đạt chuẩn quốc tế FIFA.</div>
                    </div>
                    <div style="padding:10px;border-left:3px solid #f59e0b;background:var(--bg-panel)">
                        <div><b style="color:#f59e0b">IC (Indoor Court)</b>: Đế cao su phẳng bám dính dành cho thi đấu futsal và sàn gỗ trong nhà.</div>
                    </div>
                </div>
            </div>

            {{-- Foot Shape Guide --}}
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <b style="font:700 14px/1 'Oswald',sans-serif;color:var(--text-main);text-transform:uppercase">
                        TƯ VẤN PHOM BÀN CHÂN & DÒNG GIÀY TƯƠNG THÍCH
                    </b>
                    <span class="status pending" style="font-size:9px;background:#0c2a38;color:#38bdf8;border-color:#0284c7">[THỦ CÔNG]</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:14px">
                    <div style="padding:12px;border:1px solid var(--border-panel);border-radius:6px;background:var(--bg-panel)">
                        <div style="font-weight:700;color:var(--text-main);margin-bottom:4px">CHÂN BÈ (WIDE FIT)</div>
                        <div class="muted" style="font-size:11px">Phổ biến tại thị trường Việt Nam. Khuyên dùng các dòng giày thân thiện form bè: Puma Future, Nike Tiempo, adidas Copa.</div>
                    </div>

                    <div style="padding:12px;border:1px solid var(--border-panel);border-radius:6px;background:var(--bg-panel)">
                        <div style="font-weight:700;color:var(--text-main);margin-bottom:4px">CHÂN TIÊU CHUẨN (REGULAR FIT)</div>
                        <div class="muted" style="font-size:11px">Phù hợp đa dạng hầu hết các form giày tiêu chuẩn: adidas Predator, Nike Phantom GX, Mizuno Monarcida.</div>
                    </div>

                    <div style="padding:12px;border:1px solid var(--border-panel);border-radius:6px;background:var(--bg-panel)">
                        <div style="font-weight:700;color:var(--text-main);margin-bottom:4px">CHÂN THON (SLIM FIT)</div>
                        <div class="muted" style="font-size:11px">Dành cho bàn chân mu thấp, thon gọn ôm sát tối đa: Nike Mercurial Vapor, Puma Ultra.</div>
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
                <div class="muted">Tự động kích hoạt ưu đãi, áp dụng mã giảm giá và hiển thị sản phẩm theo chiến dịch sự kiện bóng đá</div>
            </div>
            <div class="actions">
                <span class="muted" style="font-size:11px">Quản lý theo thời gian thực</span>
            </div>
        </div>

        {{-- Matchday Campaigns Pipeline --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:16px;margin-bottom:24px">
            @forelse($realCampaigns as $camp)
                @php
                    $statusMap = [
                        'draft' => ['class' => 'muted', 'label' => 'BẢN NHÁP'],
                        'scheduled' => ['class' => 'pending', 'label' => 'ĐÃ LÊN LỊCH', 'style' => 'background:#1e3a8a;color:#93c5fd;border-color:#2563eb'],
                        'active' => ['class' => 'completed', 'label' => '● ĐANG CHẠY'],
                        'ended' => ['class' => 'completed', 'label' => 'ĐÃ KẾT THÚC', 'style' => 'background:#142d1f;color:#6ee7b7;border-color:#059669'],
                        'cancelled' => ['class' => 'cancelled', 'label' => 'ĐÃ HỦY'],
                    ];
                    $st = $statusMap[$camp->status] ?? ['class' => 'muted', 'label' => strtoupper($camp->status)];
                @endphp
                <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:10px;padding:18px;display:flex;flex-direction:column;justify-content:space-between">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                            <span class="status {{ $st['class'] }}" style="font-size:9px;padding:2px 6px;{{ $st['style'] ?? '' }}">{{ $st['label'] }}</span>
                            @if($camp->coupon)
                                <span class="mono" style="color:var(--lime);font-size:11px;font-weight:700">Mã: {{ $camp->coupon->code }}</span>
                            @endif
                        </div>
                        <div style="font:700 18px/1.2 'Oswald',sans-serif;color:var(--text-main);margin-bottom:6px">
                            {{ $camp->name }}
                        </div>
                        <div class="muted mono" style="font-size:11px;margin-bottom:10px">
                            {{ $camp->starts_at ? $camp->starts_at->format('d/m/Y H:i') : 'Ngay lập tức' }}
                            @if($camp->ends_at)
                                — {{ $camp->ends_at->format('d/m/Y H:i') }}
                            @endif
                        </div>
                        <div style="font-size:12px;color:var(--text-sub);line-height:1.4;margin-bottom:12px">
                            @if($camp->teamProfile)
                                <div>Đội bóng: <b style="color:var(--text-main)">{{ $camp->teamProfile->team_name }}</b></div>
                            @endif
                            <div>Sản phẩm áp dụng: <b>{{ $camp->products->count() }}</b> sản phẩm</div>
                            @if($camp->notes)
                                <div class="muted" style="margin-top:4px">{{ $camp->notes }}</div>
                            @endif
                        </div>
                    </div>
                    <div style="padding-top:12px;border-top:1px solid var(--border-panel);display:flex;justify-content:space-between;align-items:center">
                        <span class="muted mono" style="font-size:10px">{{ $camp->created_at ? $camp->created_at->diffForHumans() : '' }}</span>
                        @if($camp->status === 'draft')
                            <button class="btn small lime" type="button" onclick="updateCampaignStatus({{ $camp->id }}, 'scheduled')">Lên lịch →</button>
                        @elseif($camp->status === 'scheduled')
                            <button class="btn small lime" type="button" onclick="updateCampaignStatus({{ $camp->id }}, 'active')">Kích hoạt</button>
                        @elseif($camp->status === 'active')
                            <button class="btn small" type="button" onclick="updateCampaignStatus({{ $camp->id }}, 'ended')">Kết thúc</button>
                        @else
                            <span class="muted" style="font-size:11px">Lưu trữ</span>
                        @endif
                    </div>
                </div>
            @empty
                <div style="grid-column:1/-1;padding:32px 18px;text-align:center;border:1px dashed var(--border-panel);border-radius:8px;color:var(--text-muted);font-size:12px">
                    Chưa có chiến dịch Matchday nào.
                </div>
            @endforelse
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
            <div class="actions">
                <span class="muted" style="font-size:11px">Áp dụng tự động trên giỏ hàng</span>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:24px">
            @forelse($realCrossSellRules as $rule)
                <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
                    <div style="flex:1;min-width:280px">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                            @if($rule->is_active)
                                <span class="status completed" style="font-size:9px">ĐANG KÍCH HOẠT</span>
                            @else
                                <span class="status muted" style="font-size:9px">TẠM DỪNG</span>
                            @endif
                            <span class="mono" style="font-weight:700;color:var(--lime)">
                                Quy tắc #CR-{{ $rule->id }}: [{{ strtoupper($rule->source_type) }}] {{ $rule->source_value }}
                            </span>
                        </div>
                        <div style="font-size:13px;color:var(--text-main);margin-bottom:4px">
                            <b>Điều kiện kích hoạt:</b> Khi giỏ hàng chứa sản phẩm thuộc <b>{{ $rule->source_type }}: {{ $rule->source_value }}</b>
                        </div>
                        <div style="font-size:12px;color:var(--text-sub)">
                            <b>Sản phẩm gợi ý kèm:</b>
                            <span style="color:var(--lime);font-weight:700">
                                {{ $rule->recommendedProduct ? ($rule->recommendedProduct->brand . ' ' . $rule->recommendedProduct->name) : 'Sản phẩm đã xóa' }}
                            </span>
                            @if($rule->recommendedProduct)
                                ({{ number_format($rule->recommendedProduct->price) }} ₫)
                            @endif
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:20px">
                        <div style="text-align:right">
                            <div class="muted" style="font-size:10px;text-transform:uppercase">Độ ưu tiên</div>
                            <div class="mono" style="font-size:16px;font-weight:700;color:var(--text-main)">P-{{ $rule->priority }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div style="padding:32px 18px;text-align:center;border:1px dashed var(--border-panel);border-radius:8px;color:var(--text-muted);font-size:12px">
                    Chưa có quy tắc bán chéo nào.
                </div>
            @endforelse
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
            <div class="actions">
                <span class="status muted" style="font-size:10px">KÍCH HOẠT TỪNG GIỎ HÀNG</span>
            </div>
        </div>

        {{-- Process Explanation Notice --}}
        <div class="notice" style="background:#071c12;border-color:var(--border-sub);margin-bottom:20px">
            <span>ℹ</span>
            <div style="font-size:12px;color:var(--text-sub);line-height:1.5">
                <b style="color:var(--lime)">QUY TRÌNH CỨU GIỎ HÀNG:</b> Quản trị viên kiểm tra danh sách giỏ hàng treo quá hạn bên dưới và nhấn <b style="color:var(--lime)">GỬI VOUCHER CỨU GIỎ ⚡</b> trên từng dòng để kích hoạt mã giảm giá gửi tới khách hàng.
            </div>
        </div>

        {{-- 4 Stat Cards --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:24px">
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Giỏ hàng đang treo</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--text-main);margin-top:4px">{{ $realAbandonedCarts->count() }} giỏ</div>
            </div>
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Tổng giá trị giỏ treo</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--lime);margin-top:4px">{{ number_format($realAbandonedCarts->sum('value')) }} ₫</div>
            </div>
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Đã gửi ưu đãi cứu giỏ</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--lime);margin-top:4px">{{ $realAbandonedCarts->whereNotNull('contacted_at')->count() }} giỏ</div>
            </div>
            <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px">
                <div class="muted" style="font-size:11px;text-transform:uppercase">Chờ xử lý cứu giỏ</div>
                <div class="mono" style="font-size:26px;font-weight:700;color:var(--warning);margin-top:4px">{{ $realAbandonedCarts->whereNull('contacted_at')->count() }} giỏ</div>
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
                        <th>HOẠT ĐỘNG GẦN NHẤT</th>
                        <th>TRẠNG THÁI</th>
                        <th>THAO TÁC CỨU GIỎ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($realAbandonedCarts as $cart)
                        <tr>
                            <td>
                                <div style="font-weight:700;color:var(--text-main)">{{ $cart['customer']?->name ?? 'Khách vãng lai' }}</div>
                                <div class="muted mono" style="font-size:11px">{{ $cart['customer']?->email ?? $cart['customer']?->phone ?? '—' }}</div>
                            </td>
                            <td>
                                @foreach($cart['items'] as $item)
                                    <div style="font-size:12px;color:var(--text-main)">
                                        {{ $item->variant?->product?->name ?? 'Sản phẩm' }}
                                        @if($item->variant?->size)
                                            <span class="muted">(Size {{ $item->variant->size }})</span>
                                        @endif
                                        <span class="mono" style="color:var(--lime)">x{{ $item->quantity }}</span>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                <b class="mono" style="color:var(--lime);font-size:14px">{{ number_format($cart['value']) }} ₫</b>
                            </td>
                            <td>
                                <div class="mono" style="font-size:11px;color:var(--text-sub)">
                                    {{ $cart['last_activity_at'] ? \Carbon\Carbon::parse($cart['last_activity_at'])->format('d/m/Y H:i') : '—' }}
                                </div>
                                <div class="muted" style="font-size:10px">
                                    {{ $cart['last_activity_at'] ? \Carbon\Carbon::parse($cart['last_activity_at'])->diffForHumans() : '' }}
                                </div>
                            </td>
                            <td>
                                @if($cart['contacted_at'])
                                    <span class="status completed" style="background:#142d1f;color:#6ee7b7;border-color:#059669">
                                        ĐÃ LIÊN HỆ
                                    </span>
                                    @if($cart['contact_coupon'])
                                        <div class="muted mono" style="font-size:10px;margin-top:2px">Mã: {{ $cart['contact_coupon']->code }}</div>
                                    @endif
                                @else
                                    <span class="status pending" style="background:#1c1917;color:#f59e0b;border-color:#b45309">
                                        CHỜ LIÊN HỆ
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($cart['contacted_at'])
                                    <span class="muted" style="font-size:11px">Đã gửi lúc {{ \Carbon\Carbon::parse($cart['contacted_at'])->format('d/m H:i') }}</span>
                                @else
                                    <button type="button" class="btn small lime" onclick="contactAbandonedCart({{ $cart['id'] }})">
                                        GỬI VOUCHER CỨU GIỎ ⚡
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:36px 16px;color:var(--text-muted)">
                                <div style="font-size:24px;margin-bottom:8px">🛒</div>
                                <div style="font-weight:700;color:var(--text-main);margin-bottom:4px">Không có giỏ hàng bị bỏ quên nào cần xử lý.</div>
                                <div style="font-size:12px">Tất cả khách hàng đều hoàn tất đơn hoặc chưa vượt ngưỡng thời gian lưu giỏ ({{ config('services.loyalty.abandoned_cart_hours', 24) }} giờ).</div>
                            </td>
                        </tr>
                    @endforelse
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
                <div class="muted">Hàng đợi kiểm duyệt chất lượng, đánh giá tình trạng thực tế và niêm yết giày ký gửi chính hãng</div>
            </div>
            <div class="actions">
                <span class="status completed">TRẠM ĐÁNH GIÁ & KIỂM DUYỆT</span>
            </div>
        </div>

        {{-- Filter Sub-Tabs --}}
        <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
            <button class="btn small lime sh-filter-btn" data-filter="all">TẤT CẢ ({{ $realSecondHandListings->count() }})</button>
            <button class="btn small sh-filter-btn" data-filter="submitted">MỚI GỬI ({{ $realSecondHandListings->where('status', 'submitted')->count() }})</button>
            <button class="btn small sh-filter-btn" data-filter="under_review">ĐANG KIỂM ĐỊNH ({{ $realSecondHandListings->where('status', 'under_review')->count() }})</button>
            <button class="btn small sh-filter-btn" data-filter="approved">ĐÃ ĐẠT CHUẨN ({{ $realSecondHandListings->where('status', 'approved')->count() }})</button>
            <button class="btn small sh-filter-btn" data-filter="listed">ĐÃ NIÊM YẾT ({{ $realSecondHandListings->where('status', 'listed')->count() }})</button>
            <button class="btn small sh-filter-btn" data-filter="sold">ĐÃ BÁN ({{ $realSecondHandListings->where('status', 'sold')->count() }})</button>
        </div>

        {{-- Second-hand Items Table --}}
        <div class="table-responsive">
            <table class="table" id="secondHandTable">
                <thead>
                    <tr>
                        <th>SẢN PHẨM & TÌNH TRẠNG</th>
                        <th>THƯƠNG HIỆU & SIZE</th>
                        <th>GIÁ KÝ GỬI & HOA HỒNG</th>
                        <th>NGƯỜI KÝ GỬI</th>
                        <th>PHƯƠNG THỨC CHI TRẢ</th>
                        <th>TRẠNG THÁI</th>
                        <th>THAO TÁC</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($realSecondHandListings as $sh)
                        <tr data-status="{{ $sh->status }}">
                            <td>
                                <div style="font-weight:700;color:var(--text-main)">{{ $sh->product_name }}</div>
                                <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
                                    <span class="status completed" style="font-size:9px">TÌNH TRẠNG: {{ $sh->condition }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="stud-badge tf">{{ $sh->brand }}</span>
                                <span class="mono" style="font-size:12px;font-weight:700;margin-left:4px">Size {{ $sh->size }}</span>
                            </td>
                            <td>
                                <div class="mono" style="font-weight:700;color:var(--lime);font-size:14px">{{ number_format($sh->asking_price) }} ₫</div>
                                @if($sh->commission_amount > 0)
                                    <div class="muted mono" style="font-size:10px">Hoa hồng: {{ number_format($sh->commission_amount) }} ₫</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $sh->user?->name ?? 'Người dùng #'.$sh->user_id }}</div>
                                <div class="muted mono" style="font-size:10px">{{ $sh->user?->phone ?? $sh->user?->email ?? '—' }}</div>
                            </td>
                            <td>
                                @if($sh->payout_method === 'voucher')
                                    <div style="font-size:11px;color:var(--lime)">Voucher mua hàng</div>
                                    @if($sh->voucher_bonus > 0)
                                        <div class="muted" style="font-size:10px">+ Thưởng {{ number_format($sh->voucher_bonus) }} ₫</div>
                                    @endif
                                @else
                                    <div style="font-size:11px;color:var(--text-sub)">Tiền mặt / Chuyển khoản</div>
                                @endif
                            </td>
                            <td>
                                @switch($sh->status)
                                    @case('submitted')
                                        <span class="status pending">MỚI GỬI DUYỆT</span>
                                        @break
                                    @case('under_review')
                                        <span class="status pending" style="background:#0c2a38;color:#38bdf8;border-color:#0284c7">ĐANG KIỂM ĐỊNH</span>
                                        @break
                                    @case('approved')
                                        <span class="status completed" style="background:#142d1f;color:#6ee7b7;border-color:#059669">ĐÃ ĐẠT CHUẨN</span>
                                        @break
                                    @case('listed')
                                        <span class="status completed">ĐANG NIÊM YẾT</span>
                                        @break
                                    @case('sold')
                                        <span class="status completed" style="background:#262626;color:#a3a3a3;border-color:#404040">ĐÃ BÁN</span>
                                        @break
                                    @case('rejected')
                                        <span class="status cancelled">TỪ CHỐI</span>
                                        @break
                                    @case('cancelled')
                                        <span class="status cancelled">ĐÃ HỦY</span>
                                        @break
                                    @default
                                        <span class="status muted">{{ $sh->status }}</span>
                                @endswitch
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    @if($sh->status === 'submitted')
                                        <button type="button" class="btn small lime" onclick="updateSecondHandStatus({{ $sh->id }}, 'under_review')">
                                            Kiểm định
                                        </button>
                                    @elseif($sh->status === 'under_review')
                                        <button type="button" class="btn small lime" onclick="updateSecondHandStatus({{ $sh->id }}, 'approved')">
                                            Duyệt đạt chuẩn
                                        </button>
                                        <button type="button" class="btn small" onclick="updateSecondHandStatus({{ $sh->id }}, 'rejected')">
                                            Từ chối
                                        </button>
                                    @elseif($sh->status === 'approved')
                                        <button type="button" class="btn small lime" onclick="updateSecondHandStatus({{ $sh->id }}, 'listed')">
                                            Niêm yết
                                        </button>
                                    @elseif($sh->status === 'listed')
                                        <button type="button" class="btn small" onclick="updateSecondHandStatus({{ $sh->id }}, 'sold')">
                                            Đã bán
                                        </button>
                                    @else
                                        <span class="muted" style="font-size:11px">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:36px 16px;color:var(--text-muted)">
                                <div style="font-size:24px;margin-bottom:8px">♻</div>
                                <div style="font-weight:700;color:var(--text-main);margin-bottom:4px">Chưa có sản phẩm second-hand chờ xử lý.</div>
                                <div style="font-size:12px">Các yêu cầu ký gửi, kiểm định giày đã qua sử dụng từ cộng đồng sẽ hiển thị tại đây.</div>
                            </td>
                        </tr>
                    @endforelse
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
                <span class="toolbar-title">🎫 HỘ CHIẾU SẢN PHẨM FIELDCRAFT</span>
                <div class="muted">Hệ thống định danh số, quản lý nguồn gốc sản phẩm và lịch sử bảo hành cho từng đôi giày</div>
            </div>
            <div class="actions">
                <span class="status completed">HỒ SƠ SỐ FIELDCRAFT</span>
            </div>
        </div>

        {{-- Interactive Passport Lookup Search --}}
        <div style="background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:16px;margin-bottom:24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <span class="muted" style="font-size:12px;font-weight:700">TRA CỨU HỘ CHIẾU:</span>
            <input id="passportSearchInput" value="{{ $realBootPassports->first()?->passport_code ?? '' }}" placeholder="Nhập mã Passport VD: FC-PASS-..." style="flex:1;min-width:240px;background:var(--bg-input);border:1px solid var(--border-panel);color:var(--lime);padding:8px 12px;border-radius:6px;font-family:'DM Mono',monospace;font-size:13px;font-weight:700">
            <button type="button" class="btn lime" onclick="lookupPassport()">TRA CỨU HỘ CHIẾU</button>
        </div>

        @if($realBootPassports->isNotEmpty())
            {{-- Passports List Table --}}
            <div class="table-responsive" style="margin-bottom:28px">
                <table class="table">
                    <thead>
                        <tr>
                            <th>MÃ PASSPORT</th>
                            <th>SẢN PHẨM & PHỐI MÀU</th>
                            <th>ĐINH & SIZE</th>
                            <th>CHỦ SỞ HỮU</th>
                            <th>NGÀY MUA</th>
                            <th>HẠN BẢO HÀNH</th>
                            <th>THAO TÁC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($realBootPassports as $bp)
                            @php
                                $bpProdName = $bp->variant?->product?->name ?? ($bp->variant_snapshot['name'] ?? 'Giày đá bóng chính hãng');
                                $bpColor = $bp->color ?? ($bp->variant_snapshot['color'] ?? '—');
                                $bpSize = $bp->size ?? ($bp->variant_snapshot['size'] ?? '—');
                                $bpStud = $bp->stud_type ?? ($bp->variant_snapshot['stud_type'] ?? 'TF');
                                $bpOrderCode = $bp->order?->order_number ?? ($bp->order_id ? 'ORD-'.$bp->order_id : '—');
                                $bpDate = $bp->purchase_date ? \Carbon\Carbon::parse($bp->purchase_date)->format('d/m/Y') : '—';
                                $bpWarranty = $bp->warranty_until ? \Carbon\Carbon::parse($bp->warranty_until)->format('d/m/Y') : 'Hết hạn bảo hành';
                                $bpOwner = $bp->user?->name ?? 'Khách hàng';
                            @endphp
                            <tr data-passport-code="{{ $bp->passport_code }}">
                                <td>
                                    <span class="mono" style="color:var(--lime);font-size:12px;font-weight:700">
                                        🎫 {{ $bp->passport_code }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:var(--text-main)">{{ $bpProdName }}</div>
                                    <div class="muted" style="font-size:11px">Phối màu: {{ $bpColor }}</div>
                                </td>
                                <td>
                                    <span class="stud-badge tf">{{ $bpStud }}</span>
                                    <span class="mono" style="font-size:12px;font-weight:700;margin-left:4px">Size {{ $bpSize }}</span>
                                </td>
                                <td>
                                    <div>{{ $bpOwner }}</div>
                                    <div class="muted mono" style="font-size:10px">{{ $bp->user?->phone ?? $bp->user?->email ?? '—' }}</div>
                                </td>
                                <td>
                                    <div class="mono" style="font-size:11px;color:var(--text-sub)">{{ $bpDate }}</div>
                                    <div class="muted mono" style="font-size:10px">Đơn: #{{ $bpOrderCode }}</div>
                                </td>
                                <td>
                                    <span class="mono" style="font-size:11px;color:var(--lime)">{{ $bpWarranty }}</span>
                                </td>
                                <td>
                                    <button type="button" class="btn small btn-view-pass" onclick="selectPassportCard('{{ $bp->passport_code }}', '{{ addslashes($bpProdName) }}', '{{ addslashes($bpColor) }}', '{{ addslashes($bpSize) }}', '{{ addslashes($bpStud) }}', '{{ addslashes($bpOrderCode) }}', '{{ addslashes($bpDate) }}', '{{ addslashes($bpWarranty) }}', '{{ addslashes($bpOwner) }}', 'THÀNH VIÊN')">
                                        Xem thẻ số
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Comprehensive Digital Passport Card Display --}}
            @php
                $firstPass = $realBootPassports->first();
                $fName = $firstPass->variant?->product?->name ?? ($firstPass->variant_snapshot['name'] ?? 'Giày đá bóng chính hãng');
                $fColor = $firstPass->color ?? ($firstPass->variant_snapshot['color'] ?? 'Tiêu chuẩn');
                $fSize = $firstPass->size ?? ($firstPass->variant_snapshot['size'] ?? '—');
                $fStud = $firstPass->stud_type ?? ($firstPass->variant_snapshot['stud_type'] ?? 'TF');
                $fOrder = $firstPass->order?->order_number ?? ($firstPass->order_id ? 'ORD-'.$firstPass->order_id : '—');
                $fDate = $firstPass->purchase_date ? \Carbon\Carbon::parse($firstPass->purchase_date)->format('d/m/Y') : '—';
                $fWarranty = $firstPass->warranty_until ? 'Đến '.\Carbon\Carbon::parse($firstPass->warranty_until)->format('d/m/Y') : 'Hết hạn bảo hành';
                $fOwner = $firstPass->user?->name ?? 'Khách hàng';
            @endphp
            <div style="display:flex;justify-content:center;margin-bottom:24px">
                <div class="passport-card-box" style="max-width:540px;width:100%">
                    <div class="passport-nfc-tag">
                        <span>HỘ CHIẾU SẢN PHẨM FIELDCRAFT</span>
                    </div>

                    <div class="passport-code-banner">
                        <span id="cardPassCode">{{ $firstPass->passport_code }}</span>
                    </div>

                    <div class="passport-shoe-title" id="cardPassName">
                        {{ $fName }}
                    </div>
                    <div class="passport-shoe-sub" id="cardPassColor">
                        Phối màu: {{ $fColor }}
                    </div>

                    <div class="passport-spec-grid">
                        <div class="passport-spec-item">
                            <div class="passport-spec-lbl">Cỡ giày & Phom dáng</div>
                            <div class="passport-spec-val" id="cardPassSize">Size {{ $fSize }}</div>
                        </div>
                        <div class="passport-spec-item">
                            <div class="passport-spec-lbl">Loại đinh mặt sân</div>
                            <div class="passport-spec-val" id="cardPassStud">{{ $fStud }}</div>
                        </div>
                        <div class="passport-spec-item">
                            <div class="passport-spec-lbl">Đơn hàng kích hoạt</div>
                            <div class="passport-spec-val" id="cardPassOrder">#{{ $fOrder }} · {{ $fDate }}</div>
                        </div>
                        <div class="passport-spec-item">
                            <div class="passport-spec-lbl">Thời hạn bảo hành keo đế</div>
                            <div class="passport-spec-val" id="cardPassWarranty" style="color:var(--lime)">{{ $fWarranty }}</div>
                        </div>
                    </div>

                    <div style="background:rgba(0,0,0,0.4);border:1px solid var(--border-panel);border-radius:8px;padding:12px;margin-bottom:0;display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div class="muted" style="font-size:10px;text-transform:uppercase">Chủ sở hữu hiện tại</div>
                            <div style="font-weight:700;color:var(--text-main)" id="cardPassOwner">{{ $fOwner }}</div>
                        </div>
                        <span class="vip-tier-badge pro" id="cardPassTier">THÀNH VIÊN</span>
                    </div>
                </div>
            </div>
        @else
            <div style="text-align:center;padding:48px 16px;color:var(--text-muted)">
                <div style="font-size:28px;margin-bottom:8px">🎫</div>
                <div style="font-weight:700;color:var(--text-main);margin-bottom:4px">Chưa có hộ chiếu sản phẩm nào được tạo.</div>
                <div style="font-size:12px">Hộ chiếu điện tử sẽ tự động cấp phát khi đơn hàng giày chính hãng được xác nhận hoàn tất.</div>
            </div>
        @endif
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
                <span class="status completed" style="font-size:10px">TỰ ĐỘNG LƯU TRỮ TOÀN VẸN</span>
            </div>
        </div>

        {{-- Chronological Audit Timeline --}}
        <div class="timeline" style="padding-left:32px">
            @forelse($realActivityLogs as $log)
                @php
                    $actionLabels = [
                        'order.status_updated' => 'Cập nhật trạng thái đơn hàng',
                        'order.status_changed' => 'Đổi trạng thái đơn hàng',
                        'abandoned_cart.mark_contacted' => 'Liên hệ giỏ hàng bị bỏ quên',
                        'second_hand.status_changed' => 'Cập nhật trạng thái ký gửi second-hand',
                        'campaign.status_updated' => 'Thay đổi trạng thái chiến dịch Matchday',
                        'campaign.approved' => 'Phê duyệt chiến dịch Matchday',
                        'cross_sell.rule_created' => 'Tạo quy tắc bán chéo mới',
                        'trend.provider_changed' => 'Cập nhật nhà cung cấp xu hướng bóng đá',
                        'team.reorder_drafted' => 'Tạo đơn đặt lại cho đội bóng',
                    ];
                    $actionTitle = $actionLabels[$log->action] ?? ($log->action ? ucfirst(str_replace(['.', '_'], ' ', $log->action)) : 'Khác');
                    $metaKeyLabels = [
                        'from_status' => 'Trạng thái cũ',
                        'to_status' => 'Trạng thái mới',
                        'status' => 'Trạng thái',
                        'coupon_id' => 'Mã ưu đãi',
                        'role' => 'Vai trò',
                        'source' => 'Nguồn',
                        'reason' => 'Lý do',
                        'note' => 'Ghi chú',
                        'order_id' => 'Mã đơn hàng',
                        'provider' => 'Đơn vị vận chuyển',
                    ];
                    $metaValLabels = [
                        'pending' => 'Chờ xử lý',
                        'confirmed' => 'Đã xác nhận',
                        'packing' => 'Đang đóng gói',
                        'preparing' => 'Đang chuẩn bị',
                        'shipping' => 'Đang giao hàng',
                        'completed' => 'Hoàn tất',
                        'cancelled' => 'Đã hủy',
                        'refund_required' => 'Cần hoàn tiền',
                        'refund_pending' => 'Đang xử lý hoàn tiền',
                        'refunded' => 'Đã hoàn tiền',
                        'refund_failed' => 'Hoàn tiền thất bại',
                    ];
                @endphp
                <article>
                    <div class="mono" style="font-size:11px;color:var(--lime);margin-bottom:4px">
                        {{ $log->created_at ? $log->created_at->format('d/m/Y · H:i:s') : '—' }}
                        @if($log->created_at)
                            <span class="muted">({{ $log->created_at->diffForHumans() }})</span>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                        <span class="status completed" style="font-size:9px">
                            {{ $log->actor ? 'QUẢN TRỊ VIÊN: ' . strtoupper($log->actor->name) : 'HỆ THỐNG TỰ ĐỘNG' }}
                        </span>
                        <span style="font-weight:700;color:var(--text-main)">{{ $actionTitle }}</span>
                    </div>
                    <div style="font-size:12px;color:var(--text-sub);line-height:1.4">
                        @if($log->subject_type)
                            <span class="mono" style="color:var(--lime)">[{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}]</span>
                        @endif
                        @if($log->metadata)
                            <span style="color:var(--text-sub)">
                                @foreach($log->metadata as $k => $v)
                                    @php
                                        $displayVal = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : ($metaValLabels[$v] ?? ($v ?: '—'));
                                    @endphp
                                    <span class="muted">{{ $metaKeyLabels[$k] ?? $k }}:</span> <b>{{ $displayVal }}</b>{{ !$loop->last ? ' · ' : '' }}
                                @endforeach
                            </span>
                        @endif
                    </div>
                </article>
            @empty
                <div style="text-align:center;padding:48px 16px;color:var(--text-muted)">
                    <div style="font-size:28px;margin-bottom:8px">📜</div>
                    <div style="font-weight:700;color:var(--text-main);margin-bottom:4px">Chưa có nhật ký hoạt động nào được ghi nhận.</div>
                    <div style="font-size:12px">Mọi hoạt động quản trị, cập nhật đơn hàng và phân quyền hệ thống sẽ được tự động lưu vết tại đây.</div>
                </div>
            @endforelse
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
            'customization': { tab: 'customization', anchor: null },
            'support': { tab: 'support', anchor: null },
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
                if (res.ok) {
                    const data = await res.json();
                    alert(`✓ Đã tạo yêu cầu đơn hàng nháp từ đội bóng "${teamName}" thành công! Trạng thái: ${data.status}, Số lượng thành viên: ${data.members?.length || 0}.`);
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || 'Không thể tạo đơn hàng nháp từ đội bóng.');
                }
            } catch (err) {
                alert('Không thể kết nối máy chủ để tạo đơn nháp.');
            } finally {
                btn.disabled = false;
                btn.innerText = '⚡ TẠO ĐƠN NHÁP TỪ ĐỘI';
            }
        });
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
async function updateOrderStatus(orderId, nextStatus) {
    const labels = {
        'confirmed': 'xác nhận đơn hàng',
        'packing': 'chuyển sang đóng gói',
        'shipping': 'chuyển sang giao hàng',
        'completed': 'hoàn tất đơn hàng'
    };
    if (!confirm(`Xác nhận ${labels[nextStatus] || nextStatus} cho đơn hàng #${orderId}?`)) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/orders/${orderId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ status: nextStatus })
        });
        if (res.ok) {
            alert(`✓ Đã cập nhật trạng thái đơn hàng #${orderId} thành công!`);
            window.location.reload();
        } else {
            const err = await res.json().catch(() => ({}));
            alert(err.message || 'Quy trình trạng thái không hợp lệ theo quy định hệ thống.');
        }
    } catch (e) {
        alert('Không thể kết nối máy chủ để cập nhật đơn hàng.');
    }
}

async function manualCompleteOrder(orderId) {
    if (!confirm(`Xác nhận hoàn tất đơn hàng #${orderId}? (Chỉ khả dụng trong môi trường thử nghiệm)`)) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/orders/${orderId}/manual-complete`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            }
        });
        if (res.ok) {
            alert(`✓ Đã xác nhận hoàn tất đơn hàng #${orderId} thành công!`);
            window.location.reload();
        } else {
            alert('Không thể hoàn tất đơn hàng. Thao tác chỉ khả dụng trong môi trường thử nghiệm.');
        }
    } catch (e) {
        alert('Không thể kết nối máy chủ để hoàn tất đơn hàng.');
    }
}

async function syncGhnOrder(orderId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/orders/${orderId}/sync-ghn`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            }
        });
        if (res.ok) {
            alert(`✓ Đã đồng bộ trạng thái GHN cho đơn hàng #${orderId}!`);
            window.location.reload();
        } else {
            alert('Không thể đồng bộ trạng thái GHN lúc này.');
        }
    } catch (e) {
        alert('Không thể kết nối máy chủ để đồng bộ GHN.');
    }
}

async function contactAbandonedCart(cartId) {
    if (!confirm('Xác nhận đánh dấu đã liên hệ và kích hoạt mã ưu đãi cho khách hàng của giỏ hàng này?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/abandoned-carts/${cartId}/contacted`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ coupon_id: null })
        });
        if (res.ok) {
            alert('✓ Đã đánh dấu liên hệ và kích hoạt ưu đãi cứu giỏ thành công!');
            window.location.reload();
        } else {
            const err = await res.json().catch(() => ({}));
            alert(err.message || 'Có lỗi xảy ra khi cập nhật giỏ hàng.');
        }
    } catch (e) {
        alert('Không thể kết nối máy chủ.');
    }
}

async function updateSecondHandStatus(listingId, nextStatus) {
    const labels = {
        'under_review': 'bắt đầu kiểm định',
        'approved': 'duyệt đạt chuẩn',
        'listed': 'niêm yết bán',
        'sold': 'đánh dấu đã bán',
        'rejected': 'từ chối'
    };
    if (!confirm(`Xác nhận ${labels[nextStatus] || nextStatus} cho sản phẩm second-hand này?`)) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/second-hand/${listingId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ status: nextStatus })
        });
        if (res.ok) {
            alert('✓ Đã cập nhật trạng thái sản phẩm second-hand thành công!');
            window.location.reload();
        } else {
            const err = await res.json().catch(() => ({}));
            alert(err.message || 'Không thể cập nhật trạng thái theo quy trình kiểm định.');
        }
    } catch (e) {
        alert('Không thể kết nối máy chủ.');
    }
}

async function updateCampaignStatus(campaignId, nextStatus) {
    if (!confirm(`Xác nhận chuyển trạng thái chiến dịch sang "${nextStatus}"?`)) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/campaigns/${campaignId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ status: nextStatus })
        });
        if (res.ok) {
            alert('✓ Đã cập nhật trạng thái chiến dịch Matchday thành công!');
            window.location.reload();
        } else {
            const err = await res.json().catch(() => ({}));
            alert(err.message || 'Có lỗi khi cập nhật chiến dịch.');
        }
    } catch (e) {
        alert('Không thể kết nối máy chủ.');
    }
}

function selectPassportCard(code, name, color, size, stud, orderCode, date, warranty, owner, tier) {
    const cardPassCode = document.getElementById('cardPassCode');
    const cardPassName = document.getElementById('cardPassName');
    const cardPassColor = document.getElementById('cardPassColor');
    const cardPassSize = document.getElementById('cardPassSize');
    const cardPassStud = document.getElementById('cardPassStud');
    const cardPassOrder = document.getElementById('cardPassOrder');
    const cardPassWarranty = document.getElementById('cardPassWarranty');
    const cardPassOwner = document.getElementById('cardPassOwner');
    const cardPassTier = document.getElementById('cardPassTier');

    if (cardPassCode) cardPassCode.innerText = code;
    if (cardPassName) cardPassName.innerText = name;
    if (cardPassColor) cardPassColor.innerText = color ? 'Phối màu: ' + color : '';
    if (cardPassSize) cardPassSize.innerText = 'Size ' + (size || '—');
    if (cardPassStud) cardPassStud.innerText = stud || 'TF';
    if (cardPassOrder) cardPassOrder.innerText = (orderCode ? '#' + orderCode : '—') + (date ? ' · ' + date : '');
    if (cardPassWarranty) cardPassWarranty.innerText = warranty || 'Hết hạn bảo hành';
    if (cardPassOwner) cardPassOwner.innerText = owner || 'Khách hàng';
    if (cardPassTier) cardPassTier.innerText = tier || 'THÀNH VIÊN';

    const cardBox = document.querySelector('.passport-card-box');
    if (cardBox) {
        cardBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function lookupPassport() {
    const input = document.getElementById('passportSearchInput');
    const code = input ? input.value.trim().toUpperCase() : '';
    if (!code) {
        alert('Vui lòng nhập mã Boot Passport cần tra cứu.');
        return;
    }
    const row = document.querySelector(`tr[data-passport-code="${code}"]`);
    if (row) {
        row.querySelector('.btn-view-pass')?.click();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        alert(`Không tìm thấy hồ sơ số của Boot Passport "${code}" trong danh sách hệ thống.`);
    }
}

// ── Customer Support Hub JS Handlers ──
window.allSupportTicketsList = @json($supportTicketsAll);
let currentSelectedTicketId = window.allSupportTicketsList.length > 0 ? window.allSupportTicketsList[0].id : null;

function renderTicketMessages(ticket) {
    const thread = document.getElementById('ticketMessagesThread');
    if (!thread) return;
    thread.innerHTML = '';
    const messages = ticket.messages || [];
    if (messages.length === 0) {
        thread.innerHTML = '<div class="muted" style="text-align:center;padding:20px;font-size:12px">Chưa có tin nhắn trong cuộc trò chuyện này.</div>';
        return;
    }

    messages.forEach(msg => {
        const isCustomer = (msg.sender_role === 'customer');
        const bubble = document.createElement('div');
        bubble.style.display = 'flex';
        bubble.style.flexDirection = 'column';
        bubble.style.alignItems = isCustomer ? 'flex-start' : 'flex-end';
        bubble.style.marginBottom = '12px';

        const senderName = msg.sender?.name || (isCustomer ? 'Khách hàng' : 'Fieldcraft Support');
        const timeStr = msg.created_at ? new Date(msg.created_at).toLocaleString('vi-VN') : '';

        bubble.innerHTML = `
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;font-size:11px;color:var(--text-muted)">
                <span>${senderName}</span>
                <span class="status ${isCustomer ? 'muted' : 'completed'}" style="font-size:9px;padding:1px 5px">
                    ${isCustomer ? 'Khách hàng' : 'Admin Support'}
                </span>
                <span>${timeStr}</span>
            </div>
            <div style="max-width:80%;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.45;${isCustomer ? 'background:var(--bg-panel);border:1px solid var(--border-panel);color:var(--text-main);' : 'background:#132a1e;border:1px solid var(--lime);color:var(--text-main);'}">
                ${(msg.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')}
            </div>
        `;
        thread.appendChild(bubble);
    });

    thread.scrollTop = thread.scrollHeight;
}

function selectSupportTicket(ticketId) {
    currentSelectedTicketId = ticketId;
    const ticket = window.allSupportTicketsList.find(t => t.id === ticketId);
    if (!ticket) return;

    document.querySelectorAll('.support-ticket-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.id) === ticketId);
        el.style.borderColor = parseInt(el.dataset.id) === ticketId ? 'var(--lime)' : 'var(--border-panel)';
        el.style.background = parseInt(el.dataset.id) === ticketId ? 'var(--bg-panel-sub)' : 'var(--bg-panel)';
    });

    const idEl = document.getElementById('ticketDetailId');
    const subjEl = document.getElementById('ticketDetailSubject');
    const catEl = document.getElementById('ticketDetailCategory');
    const statusBadge = document.getElementById('ticketDetailStatusBadge');
    const statusSelect = document.getElementById('ticketStatusSelect');
    const customerLink = document.getElementById('ticketDetailCustomerLink');
    const orderWrap = document.getElementById('ticketDetailOrderWrap');

    if (idEl) idEl.textContent = '#' + ticket.id;
    if (subjEl) subjEl.textContent = ticket.subject;
    if (catEl) catEl.textContent = ticket.category;
    if (statusBadge) {
        statusBadge.textContent = ticket.status;
        statusBadge.className = 'status ' + (ticket.status === 'resolved' || ticket.status === 'closed' ? 'completed' : 'pending');
    }
    if (statusSelect) statusSelect.value = ticket.status;

    if (customerLink && ticket.user) {
        customerLink.href = `/admin/customers/${ticket.user_id}`;
        customerLink.textContent = `${ticket.user.name} (${ticket.user.email})`;
    }

    if (orderWrap) {
        if (ticket.order) {
            orderWrap.innerHTML = `Đơn: <a href="/admin/orders/${ticket.order_id}" class="lime-link mono" target="_blank">#${ticket.order.number}</a>`;
        } else {
            orderWrap.innerHTML = '';
        }
    }

    renderTicketMessages(ticket);
}

function filterSupportTickets(filterStatus) {
    document.querySelectorAll('.support-filter-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.filter === filterStatus);
        b.classList.toggle('lime', b.dataset.filter === filterStatus);
    });

    document.querySelectorAll('.support-ticket-item').forEach(item => {
        if (filterStatus === 'all' || item.dataset.status === filterStatus) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function searchSupportTickets(query) {
    const q = query.trim().toLowerCase();
    document.querySelectorAll('.support-ticket-item').forEach(item => {
        const id = item.dataset.id || '';
        const subject = item.dataset.subject || '';
        const user = item.dataset.user || '';
        const order = item.dataset.order || '';
        const match = !q || id.includes(q) || subject.includes(q) || user.includes(q) || order.includes(q);
        item.style.display = match ? 'block' : 'none';
    });
}

async function changeTicketStatus(newStatus) {
    if (!currentSelectedTicketId) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/support/tickets/${currentSelectedTicketId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ status: newStatus })
        });
        const data = await res.json();
        if (res.ok) {
            const ticket = window.allSupportTicketsList.find(t => t.id === currentSelectedTicketId);
            if (ticket) ticket.status = newStatus;
            selectSupportTicket(currentSelectedTicketId);
            const listItem = document.querySelector(`.support-ticket-item[data-id="${currentSelectedTicketId}"]`);
            if (listItem) listItem.dataset.status = newStatus;
        } else {
            alert(data.message || 'Không thể chuyển trạng thái ticket theo quy trình.');
        }
    } catch (e) {
        alert('Lỗi kết nối máy chủ.');
    }
}

async function submitSupportReply(e) {
    e.preventDefault();
    if (!currentSelectedTicketId) return;
    const textarea = document.getElementById('supportReplyMessage');
    const msg = textarea ? textarea.value.trim() : '';
    if (!msg) return;

    const btn = document.getElementById('btnSendSupportReply');
    btn.disabled = true;
    btn.textContent = 'Đang gửi...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/support/tickets/${currentSelectedTicketId}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ message: msg })
        });
        const data = await res.json();
        if (res.ok) {
            textarea.value = '';
            const freshTicket = data.data;
            const idx = window.allSupportTicketsList.findIndex(t => t.id === currentSelectedTicketId);
            if (idx !== -1) {
                window.allSupportTicketsList[idx] = freshTicket;
            }
            selectSupportTicket(currentSelectedTicketId);
        } else {
            alert(data.message || 'Không thể gửi phản hồi.');
        }
    } catch (e) {
        alert('Lỗi kết nối máy chủ.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'GỬI PHẢN HỒI ↵';
    }
}

// ── Admin Loyalty Voucher Modal JS Handlers ──
function openAdminLoyaltyVoucherModal(userId, userName) {
    const modal = document.getElementById('adminLoyaltyVoucherModal');
    const title = document.getElementById('adminLoyaltyVoucherTitle');
    const inputId = document.getElementById('adminLoyaltyVoucherUserId');
    if (inputId) inputId.value = userId;
    if (title) title.textContent = `🎁 TẶNG VOUCHER CHO: ${userName}`;
    if (modal) modal.style.display = 'flex';
}
function closeAdminLoyaltyVoucherModal() {
    const modal = document.getElementById('adminLoyaltyVoucherModal');
    if (modal) modal.style.display = 'none';
    const errBox = document.getElementById('adminLoyaltyVoucherError');
    if (errBox) errBox.style.display = 'none';
}

async function submitAdminLoyaltyVoucher(e) {
    e.preventDefault();
    const form = e.target;
    const userId = document.getElementById('adminLoyaltyVoucherUserId')?.value;
    if (!userId) return;

    const btn = document.getElementById('btnSubmitAdminLoyaltyVoucher');
    const errBox = document.getElementById('adminLoyaltyVoucherError');
    if (errBox) { errBox.style.display = 'none'; errBox.innerHTML = ''; }
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

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/admin/loyalty/customers/${userId}/vouchers`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            alert('✓ Đã cấp voucher cá nhân thành công cho khách hàng!');
            closeAdminLoyaltyVoucherModal();
            window.location.reload();
        } else {
            let msg = data.message || 'Lỗi khi cấp voucher.';
            if (data.errors) {
                msg = Object.values(data.errors).flat().join('<br>');
            }
            if (errBox) {
                errBox.innerHTML = msg;
                errBox.style.display = 'block';
            }
        }
    } catch (err) {
        if (errBox) {
            errBox.innerHTML = 'Không thể kết nối máy chủ.';
            errBox.style.display = 'block';
        }
    } finally {
        btn.disabled = false;
        btn.textContent = 'XÁC NHẬN CẤP VOUCHER';
    }
}

// Auto-select first ticket if tickets exist on load
if (currentSelectedTicketId) {
    selectSupportTicket(currentSelectedTicketId);
}
</script>
@endsection
