<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực email — Fieldcraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #07110d;
            --bg-panel: #0d1e16;
            --bg-panel-sub: #11261c;
            --border-panel: #1b3829;
            --border-input: #234633;
            --neon-green: #caff39;
            --neon-green-hover: #b8ec2e;
            --text-main: #ffffff;
            --text-sub: #c9d8cc;
            --text-muted: #8ea492;
            --success-bg: rgba(202, 255, 57, 0.12);
            --success-border: rgba(202, 255, 57, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--bg-body);
            color: var(--text-main);
            font: 500 14px/1.5 'Manrope', sans-serif;
            -webkit-font-smoothing: antialiased;
            padding: 20px;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }

        .card {
            width: min(440px, 100%);
            padding: 38px 32px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
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

        h1 {
            font: 700 32px/1.1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 28px 0 10px;
            color: var(--text-main);
        }
        p {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .ok {
            margin-top: 20px;
            padding: 12px 14px;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            border-radius: 6px;
            color: var(--neon-green);
            font-size: 12px;
            font-weight: 600;
        }

        .btn-submit {
            width: 100%;
            min-height: 48px;
            margin-top: 24px;
            border: 0;
            border-radius: 6px;
            background: var(--neon-green);
            color: #07110d;
            font: 700 13px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background .15s, transform .1s;
        }
        .btn-submit:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }

        .links {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            text-align: center;
        }
        .link {
            color: var(--text-muted);
            font-size: 12px;
            transition: color .15s;
        }
        .link:hover {
            color: var(--neon-green);
        }
    </style>
</head>
<body>
    <main class="card">
        <a class="brand" href="{{ route('store.home') }}">
            <i class="brand-mark"></i>FIELDCRAFT
        </a>

        <h1>Xác thực email</h1>
        <p>Chúng tôi đã gửi một liên kết xác thực đến email của bạn. Hãy kiểm tra hộp thư (kể cả mục spam) và bấm liên kết để tiếp tục mua sắm.</p>

        @if(session('status'))
            <div class="ok">✓ {{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn-submit" type="submit">GỬI LẠI EMAIL XÁC THỰC</button>
        </form>

        <div class="links">
            <a class="link" href="{{ route('settings') }}">Quay lại cài đặt</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="link" style="border:0;background:transparent;cursor:pointer;width:100%">Đăng xuất</button>
            </form>
        </div>
    </main>
</body>
</html>
