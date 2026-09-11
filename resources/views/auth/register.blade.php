<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo tài khoản — Fieldcraft</title>
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
            --border-input: #234633;
            --neon-green: #caff39;
            --neon-green-hover: #b8ec2e;
            --text-main: #ffffff;
            --text-sub: #c9d8cc;
            --text-muted: #8ea492;
            --error-text: #ff6b4a;
            --error-bg: #27110a;
            --error-border: #522115;
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
        button, input { font: inherit; }

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
            margin: 28px 0 8px;
            color: var(--text-main);
        }
        p.subtitle {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-top: 16px;
            font: 700 11px/1 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-sub);
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            margin-top: 8px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: 6px;
            color: var(--text-main);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus {
            border-color: var(--neon-green);
            box-shadow: 0 0 0 2px rgba(202, 255, 57, 0.2);
        }

        .btn-submit {
            width: 100%;
            min-height: 48px;
            margin-top: 26px;
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

        .error-alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            border-radius: 6px;
            color: var(--error-text);
            font-size: 12px;
            font-weight: 600;
        }

        .footer-links {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        .footer-link {
            color: var(--text-muted);
            font-size: 12px;
            transition: color .15s;
        }
        .footer-link:hover {
            color: var(--neon-green);
        }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('register.store') }}">
        @csrf
        <a class="brand" href="{{ route('store.home') }}">
            <i class="brand-mark"></i>FIELDCRAFT
        </a>

        <h1>Tạo tài khoản</h1>
        <p class="subtitle">Lưu đơn mua và quản lý hồ sơ của bạn.</p>

        @if($errors->any())
            <div class="error-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <label>
            Họ và tên
            <input name="name" type="text" required value="{{ old('name') }}" autofocus autocomplete="name">
        </label>

        <label>
            Email
            <input name="email" type="email" required value="{{ old('email') }}" autocomplete="email">
        </label>

        <label>
            Mật khẩu
            <input name="password" type="password" required autocomplete="new-password">
        </label>

        <label>
            Nhập lại mật khẩu
            <input name="password_confirmation" type="password" required autocomplete="new-password">
        </label>

        <button class="btn-submit" type="submit">ĐĂNG KÝ →</button>

        <div class="footer-links">
            <a class="footer-link" href="{{ route('login') }}">Đã có tài khoản? <strong>Đăng nhập</strong></a>
            <a class="footer-link" href="{{ route('store.home') }}">← Quay lại cửa hàng</a>
        </div>
    </form>
</body>
</html>
