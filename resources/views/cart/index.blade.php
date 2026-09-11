<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng — Fieldcraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #07110d;
            --bg-panel: #0d1e16;
            --bg-panel-sub: #11261c;
            --bg-input: #12281d;
            --border-panel: #1b3829;
            --border-sub: #234633;
            --border-input: #234633;
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
            padding-bottom: 70px;
        }
        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }

        .wrap {
            max-width: 1180px;
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
        .back-link {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            transition: color .15s;
        }
        .back-link:hover {
            color: var(--neon-green);
        }

        h1 {
            font: 700 36px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 24px;
            display: flex;
            align-items: baseline;
            gap: 12px;
            color: var(--text-main);
        }
        h1 small {
            font-size: 18px;
            color: var(--text-muted);
            font-family: 'DM Mono', monospace;
            font-weight: 500;
        }

        .alert-banner {
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 22px;
        }
        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }
        .alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--neon-green);
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 22px 24px;
        }

        .select-all-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-panel);
            margin-bottom: 8px;
        }
        .select-all-label {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            color: var(--text-main);
        }

        .custom-check {
            width: 20px;
            height: 20px;
            accent-color: var(--neon-green);
            cursor: pointer;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 28px 96px 1fr auto auto 36px;
            gap: 16px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-panel);
            transition: opacity .15s;
        }
        .cart-item:last-child {
            border-bottom: 0;
            padding-bottom: 6px;
        }
        .cart-item.unavailable {
            opacity: 0.55;
        }

        .item-thumb {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
        }

        .item-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }
        .item-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-main);
            margin: 0;
            line-height: 1.3;
        }
        .item-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 2px;
        }
        .item-badge {
            display: inline-flex;
            padding: 2px 7px;
            border-radius: 4px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            color: var(--text-sub);
            font: 600 11px/1.3 'DM Mono', monospace;
        }
        .item-sku {
            font: 500 11px 'DM Mono', monospace;
            color: var(--text-muted);
        }
        .item-unit-price {
            font: 700 14px 'DM Mono', monospace;
            color: var(--text-sub);
            margin-top: 4px;
        }
        .stock-indicator {
            font-size: 12px;
            font-weight: 600;
            color: var(--neon-green);
            margin-top: 2px;
        }
        .stock-indicator.danger {
            color: var(--error-text);
        }

        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border-input);
            border-radius: 6px;
            background: var(--bg-input);
            overflow: hidden;
        }
        .qty-stepper form {
            margin: 0;
            display: flex;
        }
        .qty-btn {
            width: 38px;
            height: 38px;
            background: transparent;
            border: 0;
            color: var(--text-main);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
        }
        .qty-btn:hover:not(:disabled) {
            background: var(--border-panel);
            color: var(--neon-green);
        }
        .qty-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .qty-input {
            width: 44px;
            height: 38px;
            border: 0;
            background: transparent;
            color: var(--text-main);
            text-align: center;
            font: 700 13px 'DM Mono', monospace;
            outline: none;
            -moz-appearance: textfield;
        }
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .item-total {
            font: 700 16px/1 'DM Mono', monospace;
            color: var(--neon-green);
            text-align: right;
            white-space: nowrap;
        }

        .btn-remove {
            width: 36px;
            height: 36px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 6px;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color .15s, background .15s, border-color .15s;
        }
        .btn-remove:hover {
            color: var(--error-text);
            background: var(--error-bg);
            border-color: var(--error-border);
        }

        .summary-card {
            position: sticky;
            top: 24px;
        }
        .summary-card h2 {
            font: 700 22px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 18px;
            color: var(--text-main);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
            font-size: 13px;
            color: var(--text-sub);
        }
        .summary-row strong {
            font: 700 15px 'DM Mono', monospace;
            color: var(--text-main);
        }
        .summary-note {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            padding: 10px 12px;
            border-radius: 6px;
            margin: 16px 0;
        }
        .summary-total {
            border-top: 1px solid var(--border-panel);
            padding-top: 16px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .summary-total-label {
            font: 700 13px 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--text-sub);
        }
        .summary-total-amount {
            font: 700 24px/1 'DM Mono', monospace;
            color: var(--neon-green);
        }

        .btn-checkout {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 52px;
            margin-top: 20px;
            background: var(--neon-green);
            color: #07110d;
            border: 0;
            border-radius: 6px;
            font: 700 14px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            transition: background .15s, transform .1s;
        }
        .btn-checkout:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }

        .continue-link {
            display: block;
            text-align: center;
            margin-top: 14px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            transition: color .15s;
        }
        .continue-link:hover {
            color: var(--neon-green);
        }

        .btn-clear {
            width: 100%;
            margin-top: 16px;
            padding: 10px;
            background: transparent;
            border: 1px dashed var(--border-panel);
            border-radius: 6px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: color .15s, border-color .15s;
        }
        .btn-clear:hover {
            color: var(--error-text);
            border-color: var(--error-border);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            display: grid;
            place-items: center;
            font-size: 32px;
        }
        .empty-state h2 {
            font: 700 28px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 10px;
            color: var(--text-main);
        }
        .empty-state p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 24px;
        }
        .btn-shop-now {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 28px;
            background: var(--neon-green);
            color: #07110d;
            border-radius: 6px;
            font: 700 13px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            transition: background .15s, transform .1s;
        }
        .btn-shop-now:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }

        /* Mobile Sticky Action Bar */
        .mobile-bottom-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-panel);
            border-top: 1px solid var(--border-panel);
            padding: 12px 16px;
            z-index: 25;
            box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.5);
        }
        .mobile-bottom-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            max-width: 600px;
            margin: auto;
        }
        .mobile-total-box {
            display: flex;
            flex-direction: column;
        }
        .mobile-total-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .mobile-total-amount {
            font: 700 18px/1 'DM Mono', monospace;
            color: var(--neon-green);
        }
        .mobile-btn-checkout {
            min-height: 48px;
            padding: 0 20px;
            background: var(--neon-green);
            color: #07110d;
            border-radius: 6px;
            font: 700 13px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .05em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .wrap {
                padding: 20px 14px 100px;
            }
            .layout {
                grid-template-columns: 1fr;
            }
            .summary-card {
                position: static;
            }
            .cart-item {
                grid-template-columns: 24px 78px 1fr 36px;
                gap: 12px;
            }
            .item-thumb {
                width: 78px;
                height: 78px;
            }
            .qty-stepper {
                grid-column: 3;
                width: max-content;
                margin-top: 8px;
            }
            .item-total {
                grid-column: 3;
                text-align: left;
                margin-top: 4px;
            }
            .btn-remove {
                grid-column: 4;
                grid-row: 1;
            }
            .mobile-bottom-bar {
                display: block;
            }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="top-bar">
        <a class="brand" href="{{ route('store.home') }}">
            <i class="brand-mark"></i>FIELDCRAFT
        </a>
        <a class="back-link" href="{{ route('store.home') }}">← Tiếp tục mua sắm</a>
    </div>

    <h1>
        Giỏ hàng
        <small>({{ $items->sum('quantity') }})</small>
    </h1>

    @if($errors->any())
        <div class="alert-banner alert-error">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert-banner alert-success">{{ session('success') }}</div>
    @endif

    @if($items->isEmpty())
        <section class="panel empty-state">
            <div class="empty-icon">⚽</div>
            <h2>Giỏ hàng của bạn đang trống</h2>
            <p>Khám phá các sản phẩm bóng đá chính hãng và trang bị cho trận đấu tiếp theo.</p>
            <a class="btn-shop-now" href="{{ route('store.home') }}">MUA SẮM NGAY →</a>
        </section>
    @else
        <div class="layout">
            <section class="panel">
                <form class="select-all-bar" method="POST" action="{{ route('cart.select-all') }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="selected" value="0">
                    <label class="select-all-label">
                        <input class="custom-check" type="checkbox" name="selected" value="1" onchange="this.form.submit()" {{ $items->where('available', true)->isNotEmpty() && $items->where('available', true)->every(fn($line) => $line['selected']) ? 'checked' : '' }}>
                        <span>Chọn tất cả ({{ $items->where('available', true)->count() }} sản phẩm khả dụng)</span>
                    </label>
                </form>

                @foreach($items as $line)
                    @php($variant = $line['variant'])
                    @php($path = $variant->product->images->first()?->path)
                    <article class="cart-item {{ $line['available'] ? '' : 'unavailable' }}">
                        <form method="POST" action="{{ route('cart.select', $variant) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="selected" value="0">
                            <input class="custom-check" type="checkbox" name="selected" value="1" onchange="this.form.submit()" {{ $line['selected'] ? 'checked' : '' }} {{ $line['available'] ? '' : 'disabled' }}>
                        </form>

                        <img class="item-thumb" src="{{ str_starts_with((string)$path, 'http') ? $path : asset('storage/'.$path) }}" alt="{{ $variant->product->name }}">

                        <div class="item-info">
                            <h2 class="item-title">{{ $variant->product->name }}</h2>
                            <div class="item-meta">
                                <span class="item-badge">{{ $variant->color }}</span>
                                <span class="item-badge">Size {{ $variant->size }}</span>
                                <span class="item-sku">SKU: {{ $variant->sku }}</span>
                            </div>
                            <div class="item-unit-price">{{ number_format($variant->price, 0, ',', '.') }}₫</div>
                            <div class="stock-indicator {{ $line['available'] ? '' : 'danger' }}">
                                @if($line['available'])
                                    ✓ Còn {{ $variant->stock }} đôi trong kho
                                @else
                                    ⚠ Số lượng vượt tồn kho (Kho còn: {{ $variant->stock }})
                                @endif
                            </div>
                        </div>

                        <div class="qty-stepper">
                            <form method="POST" action="{{ route('cart.decrease', $variant) }}">
                                @csrf
                                @method('PATCH')
                                <button class="qty-btn" type="submit" aria-label="Giảm số lượng">−</button>
                            </form>
                            <form method="POST" action="{{ route('cart.update', $variant) }}">
                                @csrf
                                @method('PUT')
                                <input class="qty-input" name="quantity" type="number" min="1" max="{{ $variant->stock }}" value="{{ $line['quantity'] }}" onchange="this.form.submit()">
                            </form>
                            <form method="POST" action="{{ route('cart.increase', $variant) }}">
                                @csrf
                                @method('PATCH')
                                <button class="qty-btn" type="submit" aria-label="Tăng số lượng" {{ $line['quantity'] >= $variant->stock ? 'disabled' : '' }}>+</button>
                            </form>
                        </div>

                        <div class="item-total">
                            {{ number_format($line['subtotal'], 0, ',', '.') }}₫
                        </div>

                        <form method="POST" action="{{ route('cart.remove', $variant) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn-remove" type="submit" title="Xóa sản phẩm" aria-label="Xóa">×</button>
                        </form>
                    </article>
                @endforeach
            </section>

            <aside class="panel summary-card">
                <h2>Tóm tắt đơn hàng</h2>

                <div class="summary-row">
                    <span>Tạm tính giỏ hàng</span>
                    <strong>{{ number_format($subtotal, 0, ',', '.') }}₫</strong>
                </div>

                <div class="summary-row">
                    <span>Sản phẩm được chọn</span>
                    <strong>{{ $selectedCount }} món</strong>
                </div>

                <div class="summary-note">
                    Phí vận chuyển GHN sẽ được tính tự động dựa trên địa chỉ giao hàng tại bước thanh toán.
                </div>

                <div class="summary-total">
                    <span class="summary-total-label">Tổng thanh toán</span>
                    <strong class="summary-total-amount">{{ number_format($selectedTotal, 0, ',', '.') }}₫</strong>
                </div>

                @if($selectedCount > 0)
                    <a class="btn-checkout" href="{{ route('checkout') }}">TIẾN HÀNH ĐẶT HÀNG →</a>
                @else
                    <div style="margin-top: 18px; padding: 12px; background: var(--warning-bg); border: 1px solid var(--warning-border); border-radius: 6px; color: var(--warning-text); font-size: 12px; font-weight: 600; text-align: center;">
                        Vui lòng chọn ít nhất một sản phẩm để tiếp tục đặt hàng.
                    </div>
                @endif

                <a class="continue-link" href="{{ route('store.home') }}">Tiếp tục mua sắm</a>

                <form method="POST" action="{{ route('cart.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn-clear" type="submit" onclick="return confirm('Bạn có chắc muốn xóa tất cả sản phẩm khỏi giỏ hàng?')">
                        Xóa toàn bộ giỏ hàng
                    </button>
                </form>
            </aside>
        </div>

        @if($selectedCount > 0)
            <div class="mobile-bottom-bar">
                <div class="mobile-bottom-content">
                    <div class="mobile-total-box">
                        <span class="mobile-total-label">Tổng ({{ $selectedCount }} món)</span>
                        <strong class="mobile-total-amount">{{ number_format($selectedTotal, 0, ',', '.') }}₫</strong>
                    </div>
                    <a class="mobile-btn-checkout" href="{{ route('checkout') }}">ĐẶT HÀNG →</a>
                </div>
            </div>
        @endif
    @endif
</main>
</body>
</html>
