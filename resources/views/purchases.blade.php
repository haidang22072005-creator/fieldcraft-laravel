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
            max-width: 960px;
            margin: auto;
            padding: 36px 20px 60px;
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
            gap: 9px;
            font: 700 24px/1 'Oswald', sans-serif;
            letter-spacing: .02em;
            color: var(--text-main);
        }
        .brand-mark {
            width: 20px;
            height: 20px;
            border-radius: 3px 10px 3px 10px;
            background: var(--neon-green);
            transform: rotate(-20deg);
            box-shadow: inset 0 0 0 5px var(--bg-body);
        }
        .nav-links {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .back-link {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            transition: color .2s;
        }
        .back-link:hover {
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
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 22px;
        }

        .order-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 22px 24px;
            margin-bottom: 18px;
            transition: border-color .2s ease;
        }
        .order-card:hover {
            border-color: #2b563e;
        }

        .order-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
            align-items: start;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .order-code-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .order-code {
            font: 700 17px/1 'DM Mono', monospace;
            color: var(--text-main);
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
            margin-top: 8px;
            align-items: center;
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
        .badge-status {
            background: rgba(202, 255, 57, 0.12);
            color: var(--neon-green);
            border: 1px solid rgba(202, 255, 57, 0.25);
        }
        .badge-cod {
            background: #152b20;
            color: #d8e8dc;
            border: 1px solid #234633;
        }
        .badge-momo {
            background: #a50064;
            color: #ffffff;
            border: 1px solid #c2187b;
        }
        .badge-success {
            background: rgba(202, 255, 57, 0.15);
            color: var(--neon-green);
            border: 1px solid rgba(202, 255, 57, 0.3);
        }
        .badge-warning {
            background: var(--warning-bg);
            color: var(--warning-text);
            border: 1px solid var(--warning-border);
        }
        .badge-danger {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
        }
        .badge-muted {
            background: #11261c;
            color: var(--text-muted);
            border: 1px solid var(--border-panel);
        }

        .order-items {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--border-panel);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 13px;
            color: var(--text-sub);
        }
        .item-details {
            display: flex;
            gap: 8px;
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

        .order-meta-col {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            height: 100%;
            gap: 16px;
        }
        .order-total-block {
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

        .btn-retry {
            width: 100%;
            min-height: 48px;
            padding: 12px 20px;
            background: var(--neon-green);
            color: #07110d;
            border: 0;
            border-radius: 6px;
            font: 700 13px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            transition: background .15s ease, transform .1s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-retry:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }

        .empty-card {
            background: var(--bg-panel);
            border: 1px dashed var(--border-panel);
            border-radius: 12px;
            padding: 50px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .btn-shop {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 16px;
            min-height: 48px;
            padding: 12px 24px;
            background: var(--neon-green);
            color: #07110d;
            border-radius: 6px;
            font: 700 13px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .wrap {
                padding: 20px 14px 40px;
            }
            .order-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            .order-meta-col {
                align-items: stretch;
                flex-direction: column;
            }
            .order-total-block {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                padding-top: 12px;
                border-top: 1px dashed var(--border-panel);
            }
            .btn-retry {
                min-height: 50px;
            }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="top-bar">
        <a class="brand" href="{{ route('store.home') }}"><i class="brand-mark"></i>FIELDCRAFT</a>
        <div class="nav-links">
            <a class="back-link" href="{{ route('store.home') }}">Cửa hàng</a>
            <a class="back-link" href="{{ route('settings') }}">Cài đặt</a>
        </div>
    </div>

    <h1>Đơn hàng đã mua</h1>

    @if($errors->has('payment'))
        <div class="alert-banner">
            {{ $errors->first('payment') }}
        </div>
    @endif

    @forelse($orders as $order)
        @php($lastPayment = $order->payments->sortByDesc('id')->first())
        @php($canRetry = $order->payment_method === 'momo'
            && $lastPayment
            && in_array($lastPayment->status, ['failed', 'cancelled'], true)
            && $order->status === 'cancelled'
            && !$order->ghn_order_code
            && $order->payment_status !== 'paid')

        <section class="order-card">
            <div class="order-grid">
                <div class="order-info">
                    <div class="order-code-row">
                        <strong class="order-code">{{ $order->number }}</strong>
                        <span class="order-date">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="order-badges">
                        <span class="badge badge-status">{{ $order->status }}</span>

                        {{-- Payment Method & Status Badges --}}
                        @if($order->payment_method === 'cod')
                            <span class="badge badge-cod">COD</span>
                            @if($order->payment_status === 'paid')
                                <span class="badge badge-success">Đã thanh toán</span>
                            @else
                                <span class="badge badge-muted">Chưa thanh toán (Thu tiền khi nhận)</span>
                            @endif
                        @elseif($order->payment_method === 'momo')
                            <span class="badge badge-momo">MoMo</span>
                            @php($momoStatus = $lastPayment?->status ?? $order->payment_status)
                            @if($momoStatus === 'paid' || $order->payment_status === 'paid')
                                <span class="badge badge-success">MoMo đã thanh toán</span>
                            @elseif($momoStatus === 'pending' || $order->status === 'pending_payment')
                                <span class="badge badge-warning">MoMo đang chờ thanh toán</span>
                            @elseif($momoStatus === 'failed')
                                <span class="badge badge-danger">MoMo thanh toán thất bại</span>
                            @elseif($momoStatus === 'cancelled')
                                <span class="badge badge-danger">MoMo giao dịch đã hủy</span>
                            @else
                                <span class="badge badge-muted">MoMo {{ $momoStatus }}</span>
                            @endif
                        @else
                            <span class="badge badge-muted">{{ $order->payment_method }}</span>
                        @endif
                    </div>

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
                </div>

                <div class="order-meta-col">
                    <div class="order-total-block">
                        <span class="order-total-label">Tổng thanh toán</span>
                        <strong class="order-total">{{ number_format($order->total, 0, ',', '.') }}₫</strong>
                    </div>

                    @if($canRetry)
                        <form method="POST" action="{{ route('momo.retry', $order) }}" style="width: 100%;">
                            @csrf
                            <button class="btn-retry" type="submit">THỬ THANH TOÁN LẠI →</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    @empty
        <div class="empty-card">
            <p>Bạn chưa có đơn hàng nào.</p>
            <a class="btn-shop" href="{{ route('store.home') }}">Khám phá sản phẩm →</a>
        </div>
    @endforelse
</main>
</body>
</html>
