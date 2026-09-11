<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng #{{ $order->number }} — Fieldcraft</title>
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
            max-width: 1120px;
            margin: auto;
            padding: 36px 20px 80px;
        }

        /* Top Bar */
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color .15s;
        }
        .nav-link:hover {
            color: var(--neon-green);
        }

        /* Header block */
        .order-header-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .order-title {
            font: 700 34px/1.1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 8px;
            color: var(--text-main);
        }
        .order-meta-sub {
            color: var(--text-muted);
            font-size: 13px;
            font-family: 'DM Mono', monospace;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .badges-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
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

        /* Alerts */
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

        /* 2-Column Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: 1.65fr 1fr;
            gap: 24px;
            align-items: start;
        }
        .grid-col {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Panel / Card styling */
        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            overflow: hidden;
        }
        .panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .panel-title {
            font: 700 16px/1 'Oswald', sans-serif;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-body {
            padding: 20px;
        }

        /* Timeline Stepper */
        .stepper-track {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 10px 24px;
            position: relative;
        }
        .stepper-bg-line {
            position: absolute;
            top: 26px;
            left: 36px;
            right: 36px;
            height: 2px;
            background: var(--border-panel);
            z-index: 1;
        }
        .stepper-active-line {
            height: 100%;
            background: var(--neon-green);
            transition: width .3s ease;
        }
        .stepper-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-align: center;
            min-width: 60px;
        }
        .stepper-node {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--bg-panel-sub);
            border: 2px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            transition: all .2s ease;
        }
        .stepper-step.completed .stepper-node {
            background: var(--neon-green);
            border-color: var(--neon-green);
            color: #07110d;
            box-shadow: 0 0 10px rgba(202, 255, 57, 0.4);
        }
        .stepper-step.active .stepper-node {
            background: var(--bg-body);
            border-color: var(--neon-green);
            color: var(--neon-green);
            box-shadow: 0 0 10px rgba(202, 255, 57, 0.4);
        }
        .stepper-text {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--text-muted);
        }
        .stepper-step.completed .stepper-text,
        .stepper-step.active .stepper-text {
            color: var(--text-main);
        }

        /* Status events list */
        .timeline-events {
            border-top: 1px solid var(--border-panel);
            padding-top: 18px;
            margin-top: 6px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .timeline-event {
            display: flex;
            gap: 14px;
            font-size: 13px;
        }
        .event-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--neon-green);
            font-size: 14px;
            flex-shrink: 0;
        }
        .event-content {
            flex: 1;
        }
        .event-title {
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 2px;
        }
        .event-sub {
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.4;
        }
        .event-sub strong {
            color: var(--text-sub);
        }

        /* Order Items */
        .item-row {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-panel);
            align-items: center;
        }
        .item-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .item-row:first-child {
            padding-top: 0;
        }
        .item-thumb {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .item-thumb-placeholder {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .item-info {
            flex: 1;
            min-width: 0;
        }
        .item-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--text-main);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .item-meta-pill {
            font: 700 11px/1 'DM Mono', monospace;
            color: var(--text-muted);
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            padding: 3px 6px;
            border-radius: 3px;
        }
        .item-pricing {
            text-align: right;
            flex-shrink: 0;
        }
        .item-line-total {
            font: 700 15px/1 'DM Mono', monospace;
            color: var(--neon-green);
            margin-bottom: 3px;
        }
        .item-unit-calc {
            font-size: 12px;
            color: var(--text-muted);
            font-family: 'DM Mono', monospace;
        }

        /* Right Column Info Cards */
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .info-row {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            font-weight: 700;
        }
        .info-value {
            font-size: 13px;
            color: var(--text-main);
            line-height: 1.4;
        }
        .info-value.mono {
            font-family: 'DM Mono', monospace;
        }

        /* Tracking box */
        .ghn-track-box {
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .ghn-code {
            font: 700 14px/1 'DM Mono', monospace;
            color: var(--neon-green);
            letter-spacing: .04em;
        }
        .btn-copy {
            background: transparent;
            border: 1px solid var(--border-sub);
            color: var(--text-sub);
            padding: 5px 10px;
            border-radius: 4px;
            font: 700 11px/1 'DM Mono', monospace;
            cursor: pointer;
            transition: all .15s ease;
        }
        .btn-copy:hover {
            border-color: var(--neon-green);
            color: var(--neon-green);
        }

        /* Financial summary rows */
        .summary-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text-sub);
        }
        .summary-row.discount {
            color: var(--neon-green);
        }
        .summary-row.total {
            padding-top: 12px;
            margin-top: 4px;
            border-top: 1px solid var(--border-panel);
            font-weight: 700;
        }
        .summary-row.total .sum-label {
            font: 700 14px/1 'Oswald', sans-serif;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-main);
        }
        .summary-row.total .sum-val {
            font: 700 22px/1 'DM Mono', monospace;
            color: var(--neon-green);
        }

        /* Bottom Action Bar */
        .actions-panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 18px 24px;
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .actions-left, .actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            min-height: 44px;
            padding: 0 20px;
            border-radius: 6px;
            font: 700 12px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all .15s ease;
            border: 0;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-primary {
            background: var(--neon-green);
            color: #07110d;
        }
        .btn-primary:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            color: var(--text-sub);
        }
        .btn-secondary:hover {
            border-color: var(--border-sub);
            color: var(--text-main);
        }
        .btn-warning {
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            color: var(--warning-text);
        }
        .btn-warning:hover {
            background: #382c0e;
        }
        .btn-danger {
            background: transparent;
            border: 1px solid var(--border-panel);
            color: var(--text-muted);
        }
        .btn-danger:hover {
            background: var(--error-bg);
            border-color: var(--error-border);
            color: var(--error-text);
        }
        .btn-momo {
            background: #a50064;
            color: #fff;
            border: 1px solid #c2187b;
        }
        .btn-momo:hover {
            background: #c2187b;
        }

        @media (max-width: 860px) {
            .wrap {
                padding: 20px 14px 50px;
            }
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .order-header-box {
                flex-direction: column;
            }
            .actions-panel {
                flex-direction: column;
                align-items: stretch;
            }
            .actions-left, .actions-right {
                width: 100%;
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
                min-height: 48px;
            }
        }
    </style>
</head>
<body>
<main class="wrap">
    {{-- Top Bar Navigation --}}
    <div class="top-bar">
        <a class="brand" href="{{ route('store.home') }}"><i class="brand-mark"></i>FIELDCRAFT</a>
        <div class="nav-links">
            <a class="nav-link" href="{{ route('purchases') }}">← Đơn hàng của tôi</a>
            <a class="nav-link" href="{{ route('store.home') }}">Cửa hàng</a>
        </div>
    </div>

    {{-- Alert Messages --}}
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

    {{-- Normalizing Order & Shipping Status --}}
    @php
        $orderStatus = $order->status;
        $shipStatus = (string) ($tracking['status'] ?? $order->shipping_status);
        $payStatus = $order->payment_status;
        $lastPayment = $order->payments->sortByDesc('id')->first();

        // Status badge setup
        if ($orderStatus === 'cancelled') {
            $badgeClass = 'badge-danger';
            $badgeLabel = 'Đã hủy';
            $stepProgress = 0;
        } elseif ($orderStatus === 'completed' || $shipStatus === 'delivered') {
            $badgeClass = 'badge-success';
            $badgeLabel = 'Hoàn thành';
            $stepProgress = 3;
        } elseif (in_array($shipStatus, ['delivering', 'transporting', 'picking', 'ready_to_pick']) || $orderStatus === 'shipping') {
            $badgeClass = 'badge-info';
            $badgeLabel = 'Đang giao hàng';
            $stepProgress = 2;
        } elseif ($orderStatus === 'pending_payment' || ($order->payment_method !== 'cod' && $payStatus === 'pending')) {
            $badgeClass = 'badge-warning';
            $badgeLabel = 'Chờ thanh toán';
            $stepProgress = 0;
        } elseif ($payStatus === 'paid' || in_array($orderStatus, ['confirmed', 'preparing', 'packing'])) {
            $badgeClass = 'badge-success';
            $badgeLabel = 'Đã xác nhận / Đóng gói';
            $stepProgress = 1;
        } else {
            $badgeClass = 'badge-muted';
            $badgeLabel = 'Đã tiếp nhận';
            $stepProgress = 0;
        }

        // Cancellable check
        $isCancellable = in_array($orderStatus, ['pending', 'pending_payment', 'preparing', 'confirmed', 'packing', 'shipping'], true)
            && (! $order->ghn_order_code || in_array($shipStatus, ['pending', 'creating', 'created', 'order_created', 'confirmed', 'ready_to_pick'], true))
            && $orderStatus !== 'cancelled';

        // Shipping expedite check
        $isShipping = in_array($orderStatus, ['shipping', 'confirmed', 'preparing', 'packing'])
            && in_array($shipStatus, ['ready_to_pick', 'picking', 'transporting', 'delivering', 'order_created', 'created']);

        // Retry MoMo check
        $canRetryMomo = $order->payment_method === 'momo'
            && $lastPayment
            && in_array($lastPayment->status, ['failed', 'cancelled'], true)
            && $orderStatus === 'cancelled'
            && !$order->ghn_order_code
            && $payStatus !== 'paid';

        // Retry Bank QR check
        $canRetryBank = ($order->payment_method === 'bank_qr' || $order->payment_method === 'payos')
            && $payStatus !== 'paid'
            && in_array($orderStatus, ['pending_payment', 'pending']);
    @endphp

    {{-- Order Header --}}
    <div class="order-header-box">
        <div>
            <h1 class="order-title">Đơn hàng #{{ $order->number }}</h1>
            <div class="order-meta-sub">
                <span>Đặt ngày {{ $order->created_at?->format('d/m/Y H:i') }}</span>
                <span>·</span>
                <span>{{ $order->items->sum('quantity') }} sản phẩm</span>
            </div>
        </div>

        <div class="badges-group">
            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>

            @if($order->payment_method === 'cod')
                <span class="badge badge-muted">COD (Thu tiền khi nhận)</span>
            @elseif($order->payment_method === 'momo')
                <span class="badge" style="background:#a50064;color:#fff;border:1px solid #c2187b">MoMo</span>
            @elseif($order->payment_method === 'bank_qr' || $order->payment_method === 'payos')
                <span class="badge" style="background:#003366;color:#70d6ff;border:1px solid #0054a6">VietQR</span>
            @endif

            @if($payStatus === 'paid')
                <span class="badge badge-success">Đã thanh toán</span>
            @elseif($orderStatus === 'cancelled')
                <span class="badge badge-danger">Giao dịch hủy</span>
            @else
                <span class="badge badge-warning">Chưa thanh toán</span>
            @endif

            @if($order->ghn_order_code)
                <span class="badge badge-muted">GHN: {{ $order->ghn_order_code }}</span>
            @endif
        </div>
    </div>

    {{-- Main 2-Column Grid --}}
    <div class="detail-grid">
        {{-- Left Column: Timeline & Items --}}
        <div class="grid-col">
            {{-- Delivery Status Timeline --}}
            <section class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Tiến trình vận chuyển</h2>
                    @if($order->ghn_order_code)
                        <a href="https://donhang.ghn.vn/?order_code={{ $order->ghn_order_code }}" target="_blank" class="nav-link" style="font-size:11px;text-transform:uppercase">
                            Tra cứu GHN ↗
                        </a>
                    @endif
                </div>
                <div class="panel-body">
                    @if($orderStatus === 'cancelled')
                        <div style="background:var(--error-bg);border:1px solid var(--error-border);color:var(--error-text);padding:14px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:10px;">
                            <span style="font-size:18px">✕</span>
                            <span>Đơn hàng đã bị hủy. Toàn bộ số lượng sản phẩm trong đơn đã được hoàn lại vào kho hàng.</span>
                        </div>
                    @else
                        {{-- Stepper Track --}}
                        <div class="stepper-track">
                            <div class="stepper-bg-line">
                                <div class="stepper-active-line" style="width: {{ $stepProgress === 0 ? '0%' : ($stepProgress === 1 ? '33%' : ($stepProgress === 2 ? '66%' : '100%')) }}"></div>
                            </div>

                            <div class="stepper-step {{ $stepProgress >= 0 ? 'completed' : '' }}">
                                <div class="stepper-node">{{ $stepProgress > 0 ? '✓' : '1' }}</div>
                                <span class="stepper-text">Đã đặt</span>
                            </div>
                            <div class="stepper-step {{ $stepProgress >= 1 ? 'completed' : ($stepProgress === 0 ? 'active' : '') }}">
                                <div class="stepper-node">{{ $stepProgress > 1 ? '✓' : '2' }}</div>
                                <span class="stepper-text">Xác nhận</span>
                            </div>
                            <div class="stepper-step {{ $stepProgress >= 2 ? 'completed' : ($stepProgress === 1 ? 'active' : '') }}">
                                <div class="stepper-node">{{ $stepProgress > 2 ? '✓' : '3' }}</div>
                                <span class="stepper-text">Đang giao</span>
                            </div>
                            <div class="stepper-step {{ $stepProgress >= 3 ? 'completed' : ($stepProgress === 2 ? 'active' : '') }}">
                                <div class="stepper-node">{{ $stepProgress >= 3 ? '✓' : '4' }}</div>
                                <span class="stepper-text">Hoàn tất</span>
                            </div>
                        </div>

                        {{-- Timeline Events List --}}
                        <div class="timeline-events">
                            <div class="timeline-event">
                                <div class="event-icon">📦</div>
                                <div class="event-content">
                                    <div class="event-title">Đơn hàng đã được tạo thành công</div>
                                    <div class="event-sub">{{ $order->created_at?->format('d/m/Y H:i') }} · Đơn hàng đã tiếp nhận trên hệ thống Fieldcraft</div>
                                </div>
                            </div>

                            @if($stepProgress >= 1)
                                <div class="timeline-event">
                                    <div class="event-icon">✓</div>
                                    <div class="event-content">
                                        <div class="event-title">Đã xác nhận & Chuẩn bị đơn</div>
                                        <div class="event-sub">Kiểm tra phụ kiện, kiểm tra size và đóng gói hộp bảo vệ chuyên dụng</div>
                                    </div>
                                </div>
                            @endif

                            @if($order->ghn_order_code)
                                <div class="timeline-event">
                                    <div class="event-icon">🚚</div>
                                    <div class="event-content">
                                        <div class="event-title">Bàn giao Giao Hàng Nhanh (GHN)</div>
                                        <div class="event-sub">
                                            Mã vận đơn: <strong>{{ $order->ghn_order_code }}</strong> · Trạng thái: <strong>{{ $tracking['status'] ?? 'Đang luân chuyển' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($stepProgress >= 3)
                                <div class="timeline-event">
                                    <div class="event-icon" style="background:var(--success-bg);color:var(--neon-green)">★</div>
                                    <div class="event-content">
                                        <div class="event-title">Giao hàng thành công</div>
                                        <div class="event-sub">Kiện hàng đã được giao tới người nhận. Cảm ơn bạn đã đồng hành cùng Fieldcraft!</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            {{-- Ordered Items Card --}}
            <section class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Sản phẩm đã đặt ({{ $order->items->sum('quantity') }})</h2>
                </div>
                <div class="panel-body">
                    @foreach($order->items as $item)
                        @php($image = $item->variant?->product?->images->first()?->path)
                        <div class="item-row">
                            <div class="item-thumb">
                                @if($image)
                                    <img src="{{ asset($image) }}" alt="{{ $item->product_name }}">
                                @else
                                    <span class="item-thumb-placeholder">FC</span>
                                @endif
                            </div>

                            <div class="item-info">
                                <div class="item-name" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                <div class="item-badges">
                                    <span class="item-meta-pill">{{ $item->color }}</span>
                                    <span class="item-meta-pill">Size {{ $item->size }}</span>
                                    <span class="item-meta-pill">SKU: {{ $item->sku }}</span>
                                </div>
                                <div class="item-unit-calc">
                                    {{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }}₫
                                </div>
                            </div>

                            <div class="item-pricing">
                                <div class="item-line-total">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}₫</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Right Column: Recipient & Shipping & Payment --}}
        <div class="grid-col">
            {{-- Recipient Card --}}
            <section class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Thông tin người nhận</h2>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">Họ và tên</span>
                            <span class="info-value" style="font-weight:700">{{ $order->recipient_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Số điện thoại</span>
                            <span class="info-value mono">{{ $order->recipient_phone }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value mono">{{ $order->recipient_email }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Địa chỉ nhận hàng</span>
                            <span class="info-value">{{ $order->address_line }}, {{ $order->ward }}, {{ $order->district }}, {{ $order->province }}</span>
                        </div>
                        @if($order->note)
                            <div class="info-row">
                                <span class="info-label">Ghi chú</span>
                                <span class="info-value" style="font-style:italic;color:var(--text-sub)">"{{ $order->note }}"</span>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Shipping Carrier Card --}}
            <section class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Vận chuyển GHN</h2>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">Đơn vị vận chuyển</span>
                            <span class="info-value" style="font-weight:700">GHN Express (Giao Hàng Nhanh)</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Trạng thái giao hàng</span>
                            <span class="info-value">
                                <span class="badge {{ $badgeClass }}">{{ $tracking['status'] ?? ($order->shipping_status ?: 'Đang xử lý') }}</span>
                            </span>
                        </div>
                    </div>

                    @if($order->ghn_order_code)
                        <div class="ghn-track-box">
                            <div>
                                <div style="font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:.04em;margin-bottom:2px">Mã vận đơn GHN</div>
                                <div class="ghn-code" id="ghnCodeText">{{ $order->ghn_order_code }}</div>
                            </div>
                            <button class="btn-copy" type="button" onclick="navigator.clipboard.writeText('{{ $order->ghn_order_code }}'); this.innerText='Đã chép!'; setTimeout(()=>this.innerText='Sao chép', 2000)">
                                Sao chép
                            </button>
                        </div>
                    @else
                        <div style="margin-top:10px;font-size:12px;color:var(--text-muted);font-style:italic">
                            Mã vận đơn sẽ được tự động cập nhật khi Fieldcraft bàn giao hàng cho GHN.
                        </div>
                    @endif
                </div>
            </section>

            {{-- Payment Breakdown Card --}}
            <section class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Thanh toán</h2>
                </div>
                <div class="panel-body">
                    <div class="summary-list">
                        <div class="summary-row">
                            <span>Tạm tính</span>
                            <span style="font-family:'DM Mono',monospace">{{ number_format($order->subtotal, 0, ',', '.') }}₫</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí vận chuyển (GHN)</span>
                            <span style="font-family:'DM Mono',monospace">+{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span>
                        </div>
                        @if($order->discount > 0)
                            <div class="summary-row discount">
                                <span>Giảm giá @if($order->coupon) ({{ $order->coupon->code }}) @endif</span>
                                <span style="font-family:'DM Mono',monospace">-{{ number_format($order->discount, 0, ',', '.') }}₫</span>
                            </div>
                        @endif
                        <div class="summary-row total">
                            <span class="sum-label">Tổng thanh toán</span>
                            <span class="sum-val">{{ number_format($order->total, 0, ',', '.') }}₫</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Bottom Action Bar --}}
    <footer class="actions-panel">
        <div class="actions-left">
            <a href="{{ route('purchases') }}" class="btn-action btn-secondary">← Đơn hàng của tôi</a>
            <a href="{{ route('purchases.print', $order) }}" target="_blank" class="btn-action btn-secondary">In hóa đơn</a>
            <a href="mailto:{{ config('mail.from.address') }}?subject={{ rawurlencode('Hỗ trợ đơn hàng '.$order->number) }}" class="btn-action btn-secondary">Liên hệ hỗ trợ</a>
        </div>

        <div class="actions-right">
            @if($canRetryMomo)
                <form method="POST" action="{{ route('momo.retry', $order) }}">
                    @csrf
                    <button class="btn-action btn-momo" type="submit">Thanh toán lại MoMo →</button>
                </form>
            @endif

            @if($canRetryBank)
                <a href="{{ route('payments.bank', $order) }}" class="btn-action btn-primary">Thanh toán ngay →</a>
            @endif

            @if($isShipping)
                @if($order->expedite_requested_at)
                    <button class="btn-action btn-secondary" disabled style="opacity:0.6;cursor:not-allowed">✓ Đã hối giao hàng</button>
                @else
                    <form method="POST" action="{{ route('purchases.expedite', $order) }}">
                        @csrf
                        <button class="btn-action btn-warning" type="submit">⚡ Hối giao hàng</button>
                    </form>
                @endif
            @endif

            @if($orderStatus === 'completed' || $shipStatus === 'delivered')
                <form method="POST" action="{{ route('purchases.reorder', $order) }}">
                    @csrf
                    <button class="btn-action btn-primary" type="submit">Mua lại đơn này →</button>
                </form>
            @endif

            @if($isCancellable)
                <form method="POST" action="{{ route('purchases.cancel', $order) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                    @csrf
                    <button class="btn-action btn-danger" type="submit">Hủy đơn</button>
                </form>
            @endif
        </div>
    </footer>
</main>
</body>
</html>
