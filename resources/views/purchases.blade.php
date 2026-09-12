<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi — Fieldcraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #07110d;
            --bg-panel: #0d1e16;
            --bg-panel-sub: #11261c;
            --border-panel: #1b3829;
            --border-sub: #234633;
            --neon-green: #caff39;
            --neon-green-hover: #b8ec2e;
            --text-main: #ffffff;
            --text-sub: #c9d8cc;
            --text-muted: #8ea492;
            --error-text: #ff6b4a;
            --error-bg: #27110a;
            --error-border: #522115;
            --warning-text: #fbbf24;
            --warning-bg: #261f0c;
            --warning-border: #594717;
            --info-text: #38bdf8;
            --info-bg: rgba(56, 189, 248, 0.12);
            --info-border: rgba(56, 189, 248, 0.3);
            --success-bg: rgba(202, 255, 57, 0.12);
            --success-border: rgba(202, 255, 57, 0.25);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg-body);
            color: var(--text-main);
            font: 500 14px/1.5 'Manrope', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }

        .wrap {
            max-width: 1000px;
            margin: auto;
            padding: 36px 20px 70px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-panel);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font: 700 26px/1 'Oswald', sans-serif;
            letter-spacing: .02em;
            color: var(--text-main);
        }
        .brand-mark {
            width: 22px;
            height: 22px;
            border-radius: 3px 12px 3px 12px;
            background: var(--neon-green);
            transform: rotate(-20deg);
            box-shadow: inset 0 0 0 5px var(--bg-body);
        }
        .nav-links {
            display: flex;
            gap: 18px;
            align-items: center;
        }
        .nav-link {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            transition: color .15s;
        }
        .nav-link:hover {
            color: var(--neon-green);
        }

        h1 {
            font: 700 36px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 24px;
            color: var(--text-main);
        }

        .alert-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 22px;
        }
        .alert-banner.error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }
        .alert-banner.success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--neon-green);
        }

        .order-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
            transition: border-color .2s ease, transform .15s ease;
        }
        .order-card:hover {
            border-color: var(--border-sub);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-panel);
        }

        .order-title-box {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .order-code-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .order-code {
            font: 700 18px/1 'DM Mono', monospace;
            color: var(--text-main);
            letter-spacing: .02em;
        }
        .order-date {
            color: var(--text-muted);
            font-size: 12px;
            font-family: 'DM Mono', monospace;
        }

        .order-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 4px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font: 700 11px/1 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .badge-warning {
            background: var(--warning-bg);
            color: var(--warning-text);
            border: 1px solid var(--warning-border);
        }
        .badge-success {
            background: var(--success-bg);
            color: var(--neon-green);
            border: 1px solid var(--success-border);
        }
        .badge-info {
            background: var(--info-bg);
            color: var(--info-text);
            border: 1px solid var(--info-border);
        }
        .badge-danger {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
        }
        .badge-muted {
            background: var(--bg-panel-sub);
            color: var(--text-muted);
            border: 1px solid var(--border-panel);
        }

        .order-total-box {
            text-align: right;
        }
        .order-total-label {
            display: block;
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 2px;
        }
        .order-total {
            font: 700 22px/1 'DM Mono', monospace;
            color: var(--neon-green);
        }

        /* Mini Progress Indicator */
        .progress-stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 8px 12px;
            border-bottom: 1px solid var(--border-panel);
            position: relative;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            position: relative;
            z-index: 2;
        }
        .step-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--bg-panel-sub);
            border: 2px solid var(--border-panel);
            transition: all .2s;
        }
        .step-item.completed .step-dot {
            background: var(--neon-green);
            border-color: var(--neon-green);
            box-shadow: 0 0 10px rgba(202, 255, 57, 0.4);
        }
        .step-item.active .step-dot {
            background: var(--bg-body);
            border-color: var(--neon-green);
            box-shadow: 0 0 10px rgba(202, 255, 57, 0.4);
        }
        .step-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .step-item.completed .step-label,
        .step-item.active .step-label {
            color: var(--text-main);
        }
        .step-line {
            position: absolute;
            top: 23px;
            left: 30px;
            right: 30px;
            height: 2px;
            background: var(--border-panel);
            z-index: 1;
        }
        .step-line-fill {
            height: 100%;
            background: var(--neon-green);
            transition: width .3s;
        }

        /* Order Items preview */
        .order-items {
            padding: 16px 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text-sub);
            gap: 12px;
        }
        .item-details {
            display: flex;
            gap: 8px;
            align-items: baseline;
            flex-wrap: wrap;
        }
        .item-name {
            font-weight: 700;
            color: var(--text-main);
        }
        .item-meta {
            color: var(--text-muted);
            font-size: 12px;
        }
        .item-price {
            font-family: 'DM Mono', monospace;
            color: var(--text-sub);
            white-space: nowrap;
        }

        /* Order Actions Footer */
        .order-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid var(--border-panel);
        }
        .order-actions-left {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .order-actions-right {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-action {
            min-height: 42px;
            padding: 0 18px;
            border-radius: 6px;
            font: 700 12px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background .15s, transform .1s;
            border: 0;
        }
        .btn-view-detail {
            background: var(--neon-green);
            color: #07110d;
        }
        .btn-view-detail:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }
        .btn-pay-now {
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            color: var(--warning-text);
        }
        .btn-pay-now:hover {
            background: #382c0e;
        }
        .btn-cancel-order {
            background: transparent;
            border: 1px solid var(--border-panel);
            color: var(--text-muted);
        }
        .btn-cancel-order:hover {
            border-color: var(--error-border);
            color: var(--error-text);
            background: var(--error-bg);
        }
        .btn-reorder {
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            color: var(--neon-green);
        }
        .btn-reorder:hover {
            border-color: var(--neon-green);
            background: var(--success-bg);
        }

        /* Filter Tabs & Search */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
        }
        .tab-btn {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 6px;
            font: 700 12px/1 'Oswald', sans-serif;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .15s ease;
            white-space: nowrap;
        }
        .tab-btn:hover {
            color: var(--text-main);
            border-color: var(--border-sub);
        }
        .tab-btn.active {
            background: var(--neon-green);
            color: #07110d;
            border-color: var(--neon-green);
        }
        .search-box {
            position: relative;
            min-width: 260px;
            flex: 1;
            max-width: 380px;
        }
        .search-input {
            width: 100%;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            padding: 10px 14px 10px 36px;
            font-size: 13px;
            color: var(--text-main);
            outline: none;
            transition: border-color .15s ease;
        }
        .search-input:focus {
            border-color: var(--neon-green);
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            font-size: 13px;
        }

        .empty-card {
            background: var(--bg-panel);
            border: 1px dashed var(--border-panel);
            border-radius: 14px;
            padding: 70px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .btn-shop {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            min-height: 48px;
            padding: 12px 28px;
            background: var(--neon-green);
            color: #07110d;
            border-radius: 6px;
            font: 700 13px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .btn-shop:hover {
            background: var(--neon-green-hover);
        }

        @media (max-width: 768px) {
            .wrap {
                padding: 20px 14px 40px;
            }
            .order-header {
                flex-direction: column;
                align-items: stretch;
            }
            .order-total-box {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                padding-top: 10px;
                border-top: 1px dashed var(--border-panel);
            }
            .order-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .order-actions-left, .order-actions-right {
                width: 100%;
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
                min-height: 48px;
            }
            .progress-stepper {
                padding: 16px 0 10px;
            }
            .step-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="top-bar">
        <a class="brand" href="{{ route('store.home') }}"><i class="brand-mark"></i>FIELDCRAFT</a>
        <div class="nav-links">
            <a class="nav-link" href="{{ route('store.home') }}">Cửa hàng</a>
            <a class="nav-link" href="{{ route('settings') }}">Cài đặt</a>
        </div>
    </div>

    <h1>Đơn hàng của tôi</h1>

    @if(session('success'))
        <div class="alert-banner success">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert-banner error">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Tabs & Search Filter --}}
    <div class="filter-bar">
        <div class="tabs">
            <button type="button" class="tab-btn active" data-filter="all">Tất cả</button>
            <button type="button" class="tab-btn" data-filter="pending">Chờ xác nhận</button>
            <button type="button" class="tab-btn" data-filter="shipping">Đang giao</button>
            <button type="button" class="tab-btn" data-filter="completed">Hoàn thành</button>
            <button type="button" class="tab-btn" data-filter="cancelled">Đã hủy</button>
        </div>
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="orderSearchInput" class="search-input" placeholder="Tìm theo mã đơn #... hoặc tên sản phẩm">
        </div>
    </div>

    <div id="filterEmptyState" class="empty-card" style="display:none;margin-bottom:20px;">
        <p>Không tìm thấy đơn hàng nào phù hợp với bộ lọc tìm kiếm.</p>
    </div>

    @forelse($orders as $order)
        @php
            $lastPayment = $order->payments->sortByDesc('id')->first();
            $orderStatus = $order->status;
            $shipStatus = (string) $order->shipping_status;
            $payStatus = $order->payment_status;

            // Normalized badge info
            if ($orderStatus === 'cancelled') {
                $statusBadgeClass = 'badge-danger';
                $statusBadgeText = \App\Support\UiLabels::orderStatus($orderStatus);
                $stepProgress = 0;
            } elseif ($orderStatus === 'completed' || $shipStatus === 'delivered') {
                $statusBadgeClass = 'badge-success';
                $statusBadgeText = \App\Support\UiLabels::orderStatus('completed');
                $stepProgress = 3;
            } elseif (in_array($shipStatus, ['delivering', 'transporting', 'picking', 'ready_to_pick']) || $orderStatus === 'shipping') {
                $statusBadgeClass = 'badge-info';
                $statusBadgeText = \App\Support\UiLabels::orderStatus('shipping');
                $stepProgress = 2;
            } elseif ($orderStatus === 'pending_payment' || ($order->payment_method !== 'cod' && $payStatus === 'pending')) {
                $statusBadgeClass = 'badge-warning';
                $statusBadgeText = \App\Support\UiLabels::orderStatus('pending_payment');
                $stepProgress = 0;
            } elseif ($payStatus === 'paid' || $orderStatus === 'confirmed' || $orderStatus === 'preparing' || $orderStatus === 'packing') {
                $statusBadgeClass = 'badge-success';
                $statusBadgeText = \App\Support\UiLabels::orderStatus($orderStatus);
                $stepProgress = 1;
            } else {
                $statusBadgeClass = 'badge-muted';
                $statusBadgeText = \App\Support\UiLabels::orderStatus($orderStatus);
                $stepProgress = 0;
            }

            // Cancellable check
            $isCancellable = in_array($orderStatus, ['pending', 'pending_payment', 'preparing', 'confirmed', 'packing', 'shipping'], true)
                && (! $order->ghn_order_code || in_array($shipStatus, ['pending', 'creating', 'created', 'order_created', 'confirmed', 'ready_to_pick'], true))
                && $orderStatus !== 'cancelled';

            // Retry payment check
            $canRetryMomo = $order->payment_method === 'momo'
                && $lastPayment
                && in_array($lastPayment->status, ['failed', 'cancelled'], true)
                && $orderStatus === 'cancelled'
                && !$order->ghn_order_code
                && $payStatus !== 'paid';

            $canRetryBank = ($order->payment_method === 'bank_qr' || $order->payment_method === 'payos')
                && $payStatus !== 'paid'
                && in_array($orderStatus, ['pending_payment', 'pending']);

            // Tab filter grouping
            if ($orderStatus === 'cancelled') {
                $statusGroup = 'cancelled';
            } elseif ($orderStatus === 'completed' || $shipStatus === 'delivered') {
                $statusGroup = 'completed';
            } elseif (in_array($shipStatus, ['delivering', 'transporting', 'picking', 'ready_to_pick']) || $orderStatus === 'shipping') {
                $statusGroup = 'shipping';
            } else {
                $statusGroup = 'pending';
            }

            $searchKeywords = strtolower($order->number . ' ' . $order->items->pluck('product_name')->implode(' '));
        @endphp

        <section class="order-card" data-status-group="{{ $statusGroup }}" data-search-text="{{ $searchKeywords }}">
            <div class="order-header">
                <div class="order-title-box">
                    <div class="order-code-row">
                        <strong class="order-code">#{{ $order->number }}</strong>
                        <span class="order-date">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="order-badges">
                        <span class="badge {{ $statusBadgeClass }}">{{ $statusBadgeText }}</span>

                        @if($order->payment_method === 'cod')
                            <span class="badge badge-muted">{{ \App\Support\UiLabels::paymentMethod('cod') }}</span>
                            @if($payStatus === 'paid')
                                <span class="badge badge-success">{{ \App\Support\UiLabels::paymentStatus('paid') }}</span>
                            @else
                                <span class="badge badge-muted">{{ \App\Support\UiLabels::paymentMethod('cod') }}</span>
                            @endif
                        @elseif($order->payment_method === 'momo')
                            <span class="badge" style="background:#a50064;color:#fff;border:1px solid #c2187b">{{ \App\Support\UiLabels::paymentMethod('momo') }}</span>
                            @if($payStatus === 'paid')
                                <span class="badge badge-success">{{ \App\Support\UiLabels::paymentStatus('paid') }}</span>
                            @elseif($orderStatus === 'cancelled')
                                <span class="badge badge-danger">Giao dịch hủy</span>
                            @else
                                <span class="badge badge-warning">{{ \App\Support\UiLabels::paymentStatus('pending_payment') }}</span>
                            @endif
                        @elseif($order->payment_method === 'bank_qr' || $order->payment_method === 'payos')
                            <span class="badge" style="background:#003366;color:#70d6ff;border:1px solid #0054a6">BANK QR</span>
                            @if($payStatus === 'paid')
                                <span class="badge badge-success">{{ \App\Support\UiLabels::paymentStatus('paid') }}</span>
                            @else
                                <span class="badge badge-warning">{{ \App\Support\UiLabels::paymentStatus('pending_payment') }}</span>
                            @endif
                        @endif

                        @if($order->ghn_order_code)
                            <span class="badge badge-muted">GHN: {{ $order->ghn_order_code }}</span>
                        @endif
                    </div>
                </div>

                <div class="order-total-box">
                    <span class="order-total-label">Tổng thanh toán</span>
                    <strong class="order-total">{{ number_format($order->total, 0, ',', '.') }}₫</strong>
                </div>
            </div>

            {{-- Mini Progress Indicator --}}
            @if($orderStatus !== 'cancelled')
                <div class="progress-stepper">
                    <div class="step-line">
                        <div class="step-line-fill" style="width: {{ $stepProgress === 0 ? '0%' : ($stepProgress === 1 ? '33%' : ($stepProgress === 2 ? '66%' : '100%')) }}"></div>
                    </div>

                    <div class="step-item {{ $stepProgress >= 0 ? 'completed' : '' }}">
                        <div class="step-dot"></div>
                        <span class="step-label">Đã đặt</span>
                    </div>
                    <div class="step-item {{ $stepProgress >= 1 ? 'completed' : ($stepProgress === 0 ? 'active' : '') }}">
                        <div class="step-dot"></div>
                        <span class="step-label">Xác nhận</span>
                    </div>
                    <div class="step-item {{ $stepProgress >= 2 ? 'completed' : ($stepProgress === 1 ? 'active' : '') }}">
                        <div class="step-dot"></div>
                        <span class="step-label">Đang giao</span>
                    </div>
                    <div class="step-item {{ $stepProgress >= 3 ? 'completed' : ($stepProgress === 2 ? 'active' : '') }}">
                        <div class="step-dot"></div>
                        <span class="step-label">Hoàn tất</span>
                    </div>
                </div>
            @else
                <div style="padding: 12px 0; font-size: 12px; color: var(--error-text); display: flex; align-items: center; gap: 8px;">
                    <span>✕</span> Đơn hàng đã bị hủy. Tồn kho sản phẩm đã được tự động hoàn lại.
                </div>
            @endif

            {{-- Products list --}}
            <div class="order-items">
                @foreach($order->items as $item)
                    <div class="order-item">
                        <div class="item-details">
                            <span class="item-name">{{ $item->product_name }}</span>
                            <span class="item-meta">{{ $item->color }} / Size {{ $item->size }} × {{ $item->quantity }}</span>
                        </div>
                        <span class="item-price">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}₫</span>
                    </div>
                @endforeach
            </div>

            {{-- Action buttons footer --}}
            <div class="order-actions">
                <div class="order-actions-left">
                    @if($canRetryMomo)
                        <form method="POST" action="{{ route('momo.retry', $order) }}">
                            @csrf
                            <button class="btn-action btn-pay-now" type="submit">THANH TOÁN LẠI MOMO →</button>
                        </form>
                    @endif
                    @if($canRetryBank)
                        <a href="{{ route('payments.bank', $order) }}" class="btn-action btn-pay-now">TIẾP TỤC THANH TOÁN →</a>
                    @endif

                    @if($orderStatus === 'completed' || $shipStatus === 'delivered')
                        <form method="POST" action="{{ route('purchases.reorder', $order) }}">
                            @csrf
                            <button class="btn-action btn-reorder" type="submit">MUA LẠI</button>
                        </form>
                    @endif

                    @if($isCancellable)
                        <form method="POST" action="{{ route('purchases.cancel', $order) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                            @csrf
                            <button class="btn-action btn-cancel-order" type="submit">HỦY ĐƠN</button>
                        </form>
                    @endif
                </div>

                <div class="order-actions-right">
                    <a href="{{ route('purchases.show', $order) }}" class="btn-action btn-view-detail">XEM CHI TIẾT →</a>
                </div>
            </div>
        </section>
    @empty
        <div class="empty-card">
            <p>Bạn chưa có đơn hàng nào tại FIELDCRAFT.</p>
            <a class="btn-shop" href="{{ route('store.home') }}">Khám phá sản phẩm ngay →</a>
        </div>
    @endforelse
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-btn');
    const searchInput = document.getElementById('orderSearchInput');
    const cards = document.querySelectorAll('.order-card');
    const emptyNotice = document.getElementById('filterEmptyState');

    let activeFilter = 'all';
    let searchQuery = '';

    function filterOrders() {
        let visible = 0;
        cards.forEach(card => {
            const group = card.dataset.statusGroup || '';
            const text = card.dataset.searchText || '';
            const matchTab = (activeFilter === 'all' || group === activeFilter);
            const matchSearch = (!searchQuery || text.includes(searchQuery));

            if (matchTab && matchSearch) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });

        if (emptyNotice) {
            emptyNotice.style.display = (visible === 0 && cards.length > 0) ? 'block' : 'none';
        }
    }

    tabs.forEach(btn => {
        btn.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            filterOrders();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            filterOrders();
        });
    }
});
</script>
</body>
</html>
