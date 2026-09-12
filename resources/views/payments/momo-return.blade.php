<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán MoMo — Fieldcraft</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }

        .wrap {
            max-width: 580px;
            width: 100%;
            margin: auto;
            padding: 36px 20px 60px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
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
        .back-link {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            transition: color .2s;
        }
        .back-link:hover {
            color: var(--neon-green);
        }

        .status-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 36px 28px;
            text-align: center;
        }

        .status-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            font-size: 28px;
            font-weight: 800;
        }
        .status-icon.success {
            background: rgba(202, 255, 57, 0.15);
            border: 2px solid var(--neon-green);
            color: var(--neon-green);
        }
        .status-icon.failure {
            background: var(--error-bg);
            border: 2px solid var(--error-border);
            color: var(--error-text);
        }
        .status-icon.cancelled {
            background: #191c1a;
            border: 2px solid #323d35;
            color: var(--text-muted);
        }
        .status-icon.pending {
            background: var(--warning-bg);
            border: 2px solid var(--warning-border);
            color: var(--warning-text);
        }

        .spinner-dot {
            display: inline-block;
            width: 28px;
            height: 28px;
            border: 3px solid rgba(251, 191, 36, 0.25);
            border-top-color: var(--warning-text);
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        h1 {
            font: 700 28px/1.2 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 10px;
            color: var(--text-main);
        }
        .status-sub {
            color: var(--text-sub);
            font-size: 14px;
            margin: 0 0 26px;
            line-height: 1.5;
        }

        .details-table {
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 26px;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-panel);
            font-size: 13px;
            color: var(--text-sub);
        }
        .detail-row:last-child {
            border-bottom: 0;
        }
        .detail-row strong {
            color: var(--text-main);
            font-size: 14px;
        }
        .mono {
            font-family: 'DM Mono', monospace;
        }
        .highlight {
            color: var(--neon-green) !important;
            font-weight: 800;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 50px;
            border-radius: 6px;
            padding: 14px 20px;
            font: 700 14px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .15s ease;
            box-sizing: border-box;
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

        @media (max-width: 600px) {
            .wrap {
                padding: 24px 16px 40px;
            }
            .status-card {
                padding: 28px 18px;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="top-bar">
        <a class="brand" href="{{ route('store.home') }}"><i class="brand-mark"></i>FIELDCRAFT</a>
        <a class="back-link" href="{{ route('purchases') }}">← Đơn hàng của tôi</a>
    </div>

    <div class="status-card">
        @if($payment)
            @php($status = $payment->status)

            @if($status === 'paid')
                <div class="status-icon success" aria-hidden="true">✓</div>
                <h1>THANH TOÁN THÀNH CÔNG</h1>
                <p class="status-sub">Đơn hàng của bạn đã được thanh toán thành công qua MoMo.</p>
            @elseif($status === 'pending')
                <div class="status-icon pending" aria-hidden="true">
                    <span class="spinner-dot"></span>
                </div>
                <h1>Đang chờ kết quả thanh toán...</h1>
                <p class="status-sub">Giao dịch đang được xử lý và kiểm tra bảo mật từ cổng MoMo. Vui lòng giữ nguyên hoặc tải lại trang sau giây lát.</p>
            @elseif($status === 'failed')
                <div class="status-icon failure" aria-hidden="true">!</div>
                <h1>Thanh toán chưa thành công</h1>
                <p class="status-sub">{{ $payment->message ?: 'Giao dịch không thành công hoặc đã bị từ chối bởi cổng thanh toán MoMo.' }}</p>
            @elseif($status === 'cancelled')
                <div class="status-icon cancelled" aria-hidden="true">✕</div>
                <h1>Giao dịch đã bị hủy</h1>
                <p class="status-sub">Bạn đã hủy giao dịch trên cổng thanh toán MoMo. Đơn hàng chưa được thanh toán.</p>
            @else
                <div class="status-icon pending" aria-hidden="true">●</div>
                <h1>Trạng thái: {{ \App\Support\UiLabels::paymentStatus($status) }}</h1>
                <p class="status-sub">Kết quả cuối cùng được xác nhận an toàn qua hệ thống MoMo.</p>
            @endif

            <div class="details-table">
                <div class="detail-row">
                    <span>Mã đơn hàng</span>
                    <strong class="mono">{{ $payment->order->number }}</strong>
                </div>
                <div class="detail-row">
                    <span>Số tiền</span>
                    <strong class="mono highlight">{{ number_format($payment->amount, 0, ',', '.') }}₫</strong>
                </div>
                <div class="detail-row">
                    <span>Phương thức thanh toán</span>
                    <strong>Ví điện tử MoMo</strong>
                </div>
                @if($status === 'paid' && $payment->paid_at)
                <div class="detail-row">
                    <span>Thời gian xác nhận</span>
                    <strong class="mono">{{ $payment->paid_at->format('d/m/Y H:i:s') }}</strong>
                </div>
                @endif
            </div>

            <div class="actions">
                @if(app()->environment('local') && config('services.momo.simulator_enabled') === true && $status === 'pending')
                    <a href="{{ route('momo.sandbox', $payment->order) }}" class="btn btn-primary">THANH TOÁN QUA MOMO →</a>
                @endif
                @if(in_array($status, ['failed', 'cancelled'], true))
                    <form method="POST" action="{{ route('momo.retry', $payment->order) }}" style="display:inline-block;width:100%;">
                        @csrf
                        <button class="btn btn-primary" type="submit">THỬ THANH TOÁN LẠI →</button>
                    </form>
                    <a class="btn btn-outline" href="{{ route('purchases') }}">Xem đơn hàng</a>
                @else
                    <a class="btn btn-primary" href="{{ route('purchases') }}">Xem đơn hàng</a>
                @endif
            </div>
        @else
            <div class="status-icon failure">!</div>
            <h1>Không tìm thấy giao dịch</h1>
            <p class="status-sub">Không tìm thấy thông tin thanh toán thuộc tài khoản của bạn.</p>
            <div class="actions">
                <a class="btn btn-primary" href="{{ route('purchases') }}">Xem đơn hàng</a>
            </div>
        @endif
    </div>
</main>

@if(isset($payment) && $payment->status === 'pending')
<script>
    setTimeout(function () {
        window.location.reload();
    }, 3500);
</script>
@endif
</body>
</html>
