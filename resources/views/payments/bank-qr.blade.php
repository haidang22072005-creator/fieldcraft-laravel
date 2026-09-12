<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán VietQR — Fieldcraft</title>
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
            --vietqr-blue: #0054a6;
            --vietqr-cyan: #00b4d8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg-body);
            color: var(--text-main);
            font: 500 14px/1.5 'Manrope', sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }

        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 32px;
            border-bottom: 1px solid var(--border-panel);
            background: var(--bg-panel);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font: 700 22px/1 'Oswald', sans-serif;
            letter-spacing: .02em;
        }
        .brand-mark {
            width: 18px;
            height: 18px;
            border-radius: 3px 9px 3px 9px;
            background: var(--neon-green);
            transform: rotate(-20deg);
            box-shadow: inset 0 0 0 4px var(--bg-body);
        }
        .back-link {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            transition: color .2s;
        }
        .back-link:hover { color: var(--neon-green); }

        .page-wrap {
            max-width: 980px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 20px 60px;
            flex: 1;
        }

        .badge-vietqr {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 4px;
            background: #003366;
            color: var(--vietqr-cyan);
            border: 1px solid var(--vietqr-blue);
            font: 700 11px/1 'DM Mono', monospace;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .grid-2col {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 24px;
            align-items: start;
        }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            padding: 28px;
        }

        h1 {
            font: 700 26px/1.2 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 8px;
            color: var(--text-main);
        }

        .section-desc {
            color: var(--text-sub);
            font-size: 13px;
            margin-bottom: 22px;
            line-height: 1.5;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 0;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 10px;
            padding: 6px 18px;
            margin-bottom: 22px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-panel);
            font-size: 13px;
        }
        .info-row:last-child {
            border-bottom: 0;
        }
        .info-label {
            color: var(--text-muted);
            font-weight: 600;
        }
        .info-value-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: right;
        }
        .info-value {
            color: var(--text-main);
            font-weight: 700;
        }
        .mono {
            font-family: 'DM Mono', monospace;
        }
        .amount-highlight {
            font: 700 18px/1 'DM Mono', monospace;
            color: var(--neon-green);
        }
        .transfer-highlight {
            font: 700 15px/1 'DM Mono', monospace;
            color: var(--vietqr-cyan);
            background: rgba(0, 180, 216, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px dashed rgba(0, 180, 216, 0.35);
        }

        .btn-copy {
            background: #193829;
            border: 1px solid #29533c;
            color: var(--text-sub);
            padding: 6px 10px;
            border-radius: 4px;
            font: 700 11px/1 'DM Mono', monospace;
            cursor: pointer;
            transition: all .15s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-copy:hover {
            background: var(--neon-green);
            color: #07110d;
            border-color: var(--neon-green);
        }

        .instruction-box {
            background: rgba(0, 180, 216, 0.05);
            border: 1px solid rgba(0, 180, 216, 0.2);
            border-radius: 8px;
            padding: 14px 16px;
            color: var(--text-sub);
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 22px;
        }
        .instruction-box strong {
            color: var(--vietqr-cyan);
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
        }

        /* QR Container */
        .qr-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            padding: 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-header {
            font: 700 15px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--text-main);
            margin-bottom: 16px;
        }

        .qr-frame {
            background: #ffffff;
            padding: 14px;
            border-radius: 12px;
            box-shadow: 0 0 24px rgba(0, 0, 0, 0.4), 0 0 0 2px var(--border-sub);
            display: inline-block;
            margin-bottom: 16px;
            max-width: 280px;
            width: 100%;
        }
        .qr-image {
            width: 100%;
            height: auto;
            display: block;
            aspect-ratio: 1/1;
            object-fit: contain;
        }

        .qr-quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            width: 100%;
            margin-top: 14px;
        }
        .btn-quick-copy {
            min-height: 48px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            color: var(--text-main);
            border-radius: 6px;
            padding: 10px 14px;
            font: 700 12px/1.3 'Manrope', sans-serif;
            cursor: pointer;
            transition: all .15s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
        }
        .btn-quick-copy:hover {
            border-color: var(--neon-green);
            color: var(--neon-green);
        }
        .btn-quick-copy span.sub {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .qr-note {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 14px;
            line-height: 1.4;
        }

        /* Action Buttons */
        .actions-col {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 14px 20px;
            border-radius: 6px;
            font: 700 13px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            transition: all .15s ease;
            width: 100%;
        }
        .btn-primary {
            background: var(--neon-green);
            color: #07110d;
            border: 0;
        }
        .btn-primary:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-sub);
            color: var(--text-main);
        }
        .btn-outline:hover {
            border-color: var(--neon-green);
            color: var(--neon-green);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 4px;
            font: 700 11px/1 'DM Mono', monospace;
            text-transform: uppercase;
        }
        .status-pill.pending {
            background: var(--warning-bg);
            color: var(--warning-text);
            border: 1px solid var(--warning-border);
        }
        .status-pill.paid {
            background: rgba(202, 255, 57, 0.15);
            color: var(--neon-green);
            border: 1px solid rgba(202, 255, 57, 0.3);
        }
        .status-pill.failed {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
        }

        .spinner-dot {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(251, 191, 36, 0.3);
            border-top-color: var(--warning-text);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Full card status views (paid / failed) */
        .status-single-panel {
            max-width: 580px;
            margin: 0 auto;
            text-align: center;
            padding: 40px 28px;
        }
        .status-icon-lg {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            font-size: 28px;
            font-weight: 800;
        }
        .status-icon-lg.success {
            background: rgba(202, 255, 57, 0.15);
            border: 2px solid var(--neon-green);
            color: var(--neon-green);
        }
        .status-icon-lg.failed {
            background: var(--error-bg);
            border: 2px solid var(--error-border);
            color: var(--error-text);
        }

        /* Toast copy notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--neon-green);
            color: #07110d;
            padding: 10px 18px;
            border-radius: 6px;
            font: 700 12px/1 'Manrope', sans-serif;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
            opacity: 0;
            transform: translateY(10px);
            transition: all .2s ease;
            pointer-events: none;
            z-index: 1000;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .top-bar { padding: 14px 18px; }
            .page-wrap { padding: 20px 14px 40px; }
            .grid-2col {
                grid-template-columns: 1fr;
                gap: 18px;
            }
            .panel, .qr-card { padding: 20px 16px; }
            h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a class="brand" href="{{ route('store.home') }}"><i class="brand-mark"></i>FIELDCRAFT</a>
    <a class="back-link" href="{{ route('purchases') }}">← Đơn hàng của tôi</a>
</div>

<main class="page-wrap">
    @php
        $status = $payment?->status ?? ($order->payment_status === 'paid' ? 'paid' : 'pending');
        $amount = (int) ($payment?->amount ?? $order->total);
        $orderCode = (string) ($payment?->provider_order_id ?? $order->number);
        $transferContent = 'FC' . substr($orderCode, -7);
        $checkoutUrl = $payment?->pay_url ?? null;
        $vietqrUrl = 'https://api.vietqr.io/image/970422-0000000000-compact.jpg?amount=' . $amount . '&addInfo=' . urlencode($transferContent) . '&accountName=FIELDCRAFT';
    @endphp

    @if($status === 'paid')
        {{-- PAID SUCCESS STATE --}}
        <section class="panel status-single-panel">
            <div class="status-icon-lg success" aria-hidden="true">✓</div>
            <h1>THANH TOÁN THÀNH CÔNG</h1>
            <p class="section-desc">Đơn hàng của bạn đã được xác nhận thanh toán qua VietQR.</p>

            <div class="info-group" style="text-align: left;">
                <div class="info-row">
                    <span class="info-label">Mã đơn hàng</span>
                    <strong class="info-value mono">{{ $order->number }}</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Số tiền</span>
                    <strong class="info-value mono amount-highlight">{{ number_format($amount, 0, ',', '.') }}₫</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Phương thức</span>
                    <strong class="info-value">Chuyển khoản VietQR</strong>
                </div>
                @if($payment?->paid_at)
                    <div class="info-row">
                        <span class="info-label">Thời gian xác nhận</span>
                        <strong class="info-value mono">{{ $payment->paid_at->format('d/m/Y H:i:s') }}</strong>
                    </div>
                @endif
                @if($payment?->transaction_id)
                    <div class="info-row">
                        <span class="info-label">Mã tham chiếu</span>
                        <strong class="info-value mono">{{ $payment->transaction_id }}</strong>
                    </div>
                @endif
            </div>

            <div class="actions-col">
                <a class="btn btn-primary" href="{{ route('purchases') }}">XEM ĐƠN HÀNG →</a>
            </div>
        </section>

    @elseif(in_array($status, ['failed', 'cancelled'], true))
        {{-- FAILED / CANCELLED STATE --}}
        <section class="panel status-single-panel">
            <div class="status-icon-lg failed" aria-hidden="true">✕</div>
            <h1>Giao dịch đã bị hủy hoặc hết hạn</h1>
            <p class="section-desc">{{ $payment?->message ?: 'Giao dịch thanh toán chưa hoàn tất. Bạn có thể thử thanh toán lại.' }}</p>

            <div class="info-group" style="text-align: left;">
                <div class="info-row">
                    <span class="info-label">Mã đơn hàng</span>
                    <strong class="info-value mono">{{ $order->number }}</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Số tiền</span>
                    <strong class="info-value mono amount-highlight">{{ number_format($amount, 0, ',', '.') }}₫</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái</span>
                    <span class="status-pill failed">{{ \App\Support\UiLabels::paymentStatus($status) }}</span>
                </div>
            </div>

            <div class="actions-col">
                @if(Route::has('payos.retry'))
                    <form method="POST" action="{{ route('payos.retry', $order) }}" style="width: 100%;">
                        @csrf
                        <button class="btn btn-primary" type="submit">THANH TOÁN LẠI →</button>
                    </form>
                @endif
                <a class="btn btn-outline" href="{{ route('purchases') }}">Xem đơn hàng</a>
            </div>
        </section>

    @else
        {{-- PENDING PAYMENT: 2 COLUMNS --}}
        <div class="grid-2col">
            {{-- LEFT: Payment Info & Transfer Details --}}
            <div class="panel">
                <span class="badge-vietqr">VIETQR • THANH TOÁN NGÂN HÀNG</span>
                <h1>THANH TOÁN CHUYỂN KHOẢN</h1>
                <p class="section-desc">Mở ứng dụng ngân hàng bất kỳ để quét mã VietQR bên cạnh hoặc chuyển khoản theo thông tin dưới đây.</p>

                <div class="info-group">
                    <div class="info-row">
                        <span class="info-label">Mã đơn hàng</span>
                        <div class="info-value-wrap">
                            <span class="info-value mono">{{ $order->number }}</span>
                            <button type="button" class="btn-copy" onclick="copyText('{{ $order->number }}')">Sao chép</button>
                        </div>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Số tiền thanh toán</span>
                        <div class="info-value-wrap">
                            <span class="info-value amount-highlight">{{ number_format($amount, 0, ',', '.') }}₫</span>
                            <button type="button" class="btn-copy" onclick="copyText('{{ $amount }}')">Sao chép</button>
                        </div>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Nội dung chuyển khoản</span>
                        <div class="info-value-wrap">
                            <span class="transfer-highlight mono">{{ $transferContent }}</span>
                            <button type="button" class="btn-copy" onclick="copyText('{{ $transferContent }}')">Sao chép</button>
                        </div>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Trạng thái</span>
                        <span class="status-pill pending">
                            <span class="spinner-dot"></span> Đang chờ thanh toán...
                        </span>
                    </div>
                </div>

                <div class="instruction-box">
                    <strong>HƯỚNG DẪN THANH TOÁN</strong>
                    1. Mở ứng dụng ngân hàng bất kỳ trên điện thoại.<br>
                    2. Chọn tính năng <strong>Quét mã QR</strong> và quét mã VietQR bên cạnh.<br>
                    3. Kiểm tra đúng số tiền và nội dung chuyển khoản.<br>
                    4. Hệ thống sẽ tự động xác nhận khi giao dịch hoàn tất.
                </div>

                <div class="actions-col">
                    <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                        KIỂM TRA LẠI
                    </button>
                    @if($checkoutUrl)
                        <a href="{{ $checkoutUrl }}" target="_blank" rel="noopener" class="btn btn-outline">
                            MỞ TRANG THANH TOÁN PAYOS →
                        </a>
                    @endif
                    <a href="{{ route('purchases') }}" class="btn btn-outline">
                        Đơn hàng của tôi
                    </a>
                </div>
            </div>

            {{-- RIGHT: Large VietQR Block --}}
            <aside class="qr-card">
                <div class="qr-header">MỞ APP NGÂN HÀNG VÀ QUÉT MÃ</div>

                <div class="qr-frame">
                    <img src="{{ $vietqrUrl }}" alt="Mã VietQR thanh toán đơn hàng {{ $order->number }}" class="qr-image" id="vietqr-img">
                </div>

                <div style="font-family: 'DM Mono', monospace; font-size: 20px; font-weight: 800; color: var(--neon-green); margin-bottom: 4px;">
                    {{ number_format($amount, 0, ',', '.') }}₫
                </div>
                <div style="font-family: 'DM Mono', monospace; font-size: 13px; color: var(--vietqr-cyan);">
                    Nội dung: <strong>{{ $transferContent }}</strong>
                </div>

                <div class="qr-quick-actions">
                    <button type="button" class="btn-quick-copy" onclick="copyText('{{ $amount }}')">
                        <span>Sao chép số tiền</span>
                        <span class="sub">{{ number_format($amount, 0, ',', '.') }}₫</span>
                    </button>
                    <button type="button" class="btn-quick-copy" onclick="copyText('{{ $transferContent }}')">
                        <span>Sao chép nội dung</span>
                        <span class="sub">{{ $transferContent }}</span>
                    </button>
                </div>

                <p class="qr-note">
                    Hỗ trợ quét mã bằng tất cả ứng dụng ngân hàng và ví điện tử tại Việt Nam (MB, Vietcombank, Techcombank, BIDV, VPBank, ACB, TPBank,...).
                </p>
            </aside>
        </div>
    @endif
</main>

<div class="toast" id="toast">Đã sao chép vào bộ nhớ tạm!</div>

<script>
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(showToast);
        } else {
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            showToast();
        }
    }

    function showToast() {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2000);
    }

    @if(isset($payment) && $payment && $status === 'pending')
        // Periodic check for payment update
        (function() {
            let pollCount = 0;
            const maxPolls = 60; // 4 minutes max
            const pollInterval = 4000;

            const timer = setInterval(function() {
                pollCount++;
                if (pollCount > maxPolls) {
                    clearInterval(timer);
                    return;
                }

                @if(Route::has('payos.status'))
                    fetch('{{ route('payos.status', $payment) }}', {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.status === 'paid') {
                            clearInterval(timer);
                            window.location.reload();
                        }
                    })
                    .catch(() => {});
                @endif
            }, pollInterval);
        })();
    @endif
</script>
</body>
</html>
