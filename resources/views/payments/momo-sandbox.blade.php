<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thẻ — MoMo Sandbox</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body:         #07110d;
            --bg-panel:        #0d1e16;
            --bg-panel-sub:    #11261c;
            --border-panel:    #1b3829;
            --border-sub:      #234633;
            --neon-green:      #caff39;
            --neon-green-hover:#b8ec2e;
            --text-main:       #ffffff;
            --text-sub:        #c9d8cc;
            --text-muted:      #8ea492;
            --error-text:      #ff6b4a;
            --error-bg:        #27110a;
            --error-border:    #522115;
            --momo-pink:       #a50064;
            --momo-pink-hover: #c2187b;
            --warning-text:    #fbbf24;
            --warning-bg:      #261f0c;
            --warning-border:  #594717;
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

        /* ── Top bar ──────────────────────────────── */
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
            width: 18px; height: 18px;
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

        /* ── Env badge ────────────────────────────── */
        .env-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--warning-bg);
            border-bottom: 1px solid var(--warning-border);
            color: var(--warning-text);
            font: 700 12px/1 'DM Mono', monospace;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .env-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--warning-text);
            animation: blink 1.2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

        /* ── Layout ────────────────────────────────── */
        .page-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 36px 20px 60px;
        }
        .layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            max-width: 900px;
            width: 100%;
        }

        /* ── Panel ─────────────────────────────────── */
        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            padding: 28px 28px;
        }
        .panel-title {
            font: 700 18px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 20px;
            color: var(--text-main);
        }

        /* ── MoMo gateway logo area ─────────────────── */
        .momo-logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-panel);
        }
        .momo-logo-circle {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: var(--momo-pink);
            display: grid; place-items: center;
            font: 700 18px/1 'Oswald', sans-serif;
            color: #fff;
            flex-shrink: 0;
        }
        .momo-logo-text .momo-brand { font: 700 16px/1 'Oswald', sans-serif; color: var(--momo-pink); }
        .momo-logo-text .momo-sub { color: var(--text-muted); font-size: 12px; margin-top: 2px; }

        /* ── Order summary ───────────────────────────── */
        .summary-rows { display: flex; flex-direction: column; gap: 0; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 11px 0;
            border-bottom: 1px solid var(--border-panel);
            font-size: 13px;
            color: var(--text-sub);
        }
        .summary-row:last-child { border-bottom: 0; }
        .summary-row strong { color: var(--text-main); font-family: 'DM Mono', monospace; }
        .summary-total { font-size: 22px !important; color: var(--neon-green) !important; }

        /* ── Card form ───────────────────────────────── */
        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 6px;
        }
        .field input {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            border-radius: 8px;
            color: var(--text-main);
            font: 500 15px/1 'DM Mono', monospace;
            letter-spacing: .08em;
            transition: border-color .2s;
            outline: none;
        }
        .field input:focus { border-color: var(--momo-pink); }
        .field input::placeholder { color: var(--text-muted); letter-spacing: .04em; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* ── Error ───────────────────────────────────── */
        .error-banner {
            display: flex; align-items: center; gap: 10px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px; font-weight: 600;
            margin-bottom: 18px;
        }

        /* ── Submit button ───────────────────────────── */
        .btn-pay {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 8px;
            background: var(--momo-pink);
            color: #fff;
            font: 700 15px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .15s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-pay:hover { background: var(--momo-pink-hover); transform: translateY(-1px); }
        .btn-pay:disabled { opacity: .55; cursor: not-allowed; transform: none; }

        /* ── Test card accordion ─────────────────────── */
        .accordion {
            margin-top: 24px;
            border: 1px solid var(--border-panel);
            border-radius: 10px;
            overflow: hidden;
        }
        .accordion summary {
            display: flex; align-items: center; justify-content: space-between;
            padding: 13px 16px;
            background: var(--bg-panel-sub);
            cursor: pointer;
            font-size: 13px; font-weight: 700; color: var(--text-sub);
            list-style: none;
            user-select: none;
        }
        .accordion summary::-webkit-details-marker { display: none; }
        .accordion summary::after { content: '▾'; font-size: 14px; transition: transform .2s; }
        .accordion[open] summary::after { transform: rotate(-180deg); }
        .card-table { width: 100%; border-collapse: collapse; margin-top: 0; }
        .card-table th, .card-table td {
            padding: 10px 14px;
            text-align: left;
            font-size: 12px;
            font-family: 'DM Mono', monospace;
            border-bottom: 1px solid var(--border-panel);
        }
        .card-table th { color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; background: var(--bg-panel); }
        .card-table td { color: var(--text-sub); }
        .card-table tr:last-child td { border-bottom: 0; }
        .tag-success { color: var(--neon-green); }
        .tag-fail { color: var(--error-text); }
        .btn-fill {
            display: inline-block;
            padding: 3px 8px;
            background: var(--bg-panel);
            border: 1px solid var(--border-sub);
            border-radius: 4px;
            color: var(--text-muted);
            font-size: 11px;
            cursor: pointer;
            transition: border-color .15s, color .15s;
        }
        .btn-fill:hover { border-color: var(--neon-green); color: var(--neon-green); }

        /* ── Security note ───────────────────────────── */
        .security-note {
            margin-top: 14px;
            padding: 12px 14px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-muted);
            display: flex; gap: 8px; align-items: flex-start;
        }
        .security-note .icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

        /* ── Responsive ──────────────────────────────── */
        @media (max-width: 700px) {
            .top-bar { padding: 14px 16px; }
            .layout { grid-template-columns: 1fr; gap: 18px; }
            .page-wrap { padding: 22px 14px 48px; }
            .panel { padding: 22px 18px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a class="brand" href="{{ route('store.home') }}"><i class="brand-mark"></i>FIELDCRAFT</a>
    <a class="back-link" href="{{ route('purchases') }}">← Đơn hàng của tôi</a>
</div>

<div class="env-badge">
    <span class="env-dot"></span>
    MÔI TRƯỜNG THỬ NGHIỆM — SANDBOX LOCAL
    <span class="env-dot"></span>
</div>

<div class="page-wrap">
    <div class="layout">

        {{-- Left: Order summary --}}
        <div class="panel">
            <div class="momo-logo-area">
                <div class="momo-logo-circle">M</div>
                <div class="momo-logo-text">
                    <div class="momo-brand">MoMo</div>
                    <div class="momo-sub">Cổng thanh toán thẻ</div>
                </div>
            </div>
            <div class="panel-title">Chi tiết đơn hàng</div>
            <div class="summary-rows">
                <div class="summary-row">
                    <span>Mã đơn hàng</span>
                    <strong>{{ $order->number }}</strong>
                </div>
                <div class="summary-row">
                    <span>Phương thức</span>
                    <strong>Thẻ tín dụng / Ghi nợ</strong>
                </div>
                <div class="summary-row">
                    <span>Số tiền thanh toán</span>
                    <strong class="summary-total">{{ number_format($payment->amount, 0, ',', '.') }}₫</strong>
                </div>
            </div>

            {{-- Test card accordion --}}
            <details class="accordion">
                <summary>Danh sách thẻ thử nghiệm</summary>
                <table class="card-table">
                    <thead>
                        <tr>
                            <th>Số thẻ</th>
                            <th>Kết quả</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>9704 0000 0000 0018</td>
                            <td class="tag-success">✓ Thành công</td>
                            <td><button type="button" class="btn-fill" onclick="fillCard('9704000000000018')">Dùng</button></td>
                        </tr>
                        <tr>
                            <td>9704 0000 0000 0026</td>
                            <td class="tag-fail">✕ Thẻ bị khóa</td>
                            <td><button type="button" class="btn-fill" onclick="fillCard('9704000000000026')">Dùng</button></td>
                        </tr>
                        <tr>
                            <td>9704 0000 0000 0034</td>
                            <td class="tag-fail">✕ Không đủ số dư</td>
                            <td><button type="button" class="btn-fill" onclick="fillCard('9704000000000034')">Dùng</button></td>
                        </tr>
                        <tr>
                            <td>9704 0000 0000 0042</td>
                            <td class="tag-fail">✕ Vượt hạn mức</td>
                            <td><button type="button" class="btn-fill" onclick="fillCard('9704000000000042')">Dùng</button></td>
                        </tr>
                        <tr>
                            <td><em style="color:var(--text-muted)">Số khác</em></td>
                            <td class="tag-fail">✕ Thẻ không hợp lệ</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </details>
        </div>

        {{-- Right: Card form --}}
        <div class="panel">
            <div class="panel-title">Nhập thông tin thẻ</div>

            @if($errors->has('payment'))
                <div class="error-banner">
                    <span>⚠</span> {{ $errors->first('payment') }}
                </div>
            @endif

            <form id="sandbox-form" method="POST" action="{{ route('momo.sandbox.submit', $order) }}" autocomplete="off">
                @csrf

                <div class="field">
                    <label for="card_number">Số thẻ</label>
                    <input
                        id="card_number"
                        name="card_number"
                        type="text"
                        inputmode="numeric"
                        maxlength="19"
                        placeholder="0000 0000 0000 0000"
                        autocomplete="cc-number"
                        required
                    >
                </div>

                <div class="field">
                    <label for="card_name">Tên in trên thẻ</label>
                    <input
                        id="card_name"
                        name="card_name"
                        type="text"
                        placeholder="NGUYEN VAN A"
                        autocomplete="cc-name"
                        required
                    >
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="card_expiry">Ngày hết hạn</label>
                        <input
                            id="card_expiry"
                            name="card_expiry"
                            type="text"
                            placeholder="MM/YY"
                            maxlength="5"
                            autocomplete="cc-exp"
                            required
                        >
                    </div>
                    <div class="field">
                        <label for="card_cvc">Mã bảo mật (CVC)</label>
                        <input
                            id="card_cvc"
                            name="card_cvc"
                            type="password"
                            inputmode="numeric"
                            placeholder="•••"
                            maxlength="4"
                            autocomplete="cc-csc"
                            required
                        >
                    </div>
                </div>

                <button class="btn-pay" type="submit" id="pay-btn">
                    <span id="pay-label">THANH TOÁN {{ number_format($payment->amount, 0, ',', '.') }}₫ →</span>
                </button>
            </form>

            <div class="security-note">
                <span class="icon">🔒</span>
                <span>Đây là trang thử nghiệm sandbox. Thông tin thẻ <strong>không được lưu</strong> và <strong>không được ghi log</strong>. Chỉ sử dụng thẻ thử nghiệm ở trên.</span>
            </div>
        </div>

    </div>
</div>

<script>
    // Format card number with spaces
    document.getElementById('card_number').addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '').slice(0, 16);
        e.target.value = v.replace(/(.{4})/g, '$1 ').trim();
    });

    // Format expiry MM/YY
    document.getElementById('card_expiry').addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '').slice(0, 4);
        if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
        e.target.value = v;
    });

    // Fill card helper (called by test card buttons)
    function fillCard(number) {
        let formatted = number.replace(/(.{4})/g, '$1 ').trim();
        document.getElementById('card_number').value = formatted;
        document.getElementById('card_number').focus();
    }

    // Disable button + show spinner on submit
    document.getElementById('sandbox-form').addEventListener('submit', function () {
        const btn   = document.getElementById('pay-btn');
        const label = document.getElementById('pay-label');
        btn.disabled = true;
        label.textContent = 'ĐANG XỬ LÝ...';
    });
</script>
</body>
</html>
