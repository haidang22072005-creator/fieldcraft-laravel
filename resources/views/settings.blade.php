<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt — Fieldcraft</title>
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
        }
        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; }

        .wrap {
            max-width: 1080px;
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

        .header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .language {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 6px;
            font: 700 11px/1 'DM Mono', monospace;
            outline: none;
            cursor: pointer;
        }
        .language option {
            background: var(--bg-panel);
            color: var(--text-main);
        }
        .back-store {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            transition: color .15s;
        }
        .back-store:hover {
            color: var(--neon-green);
        }

        h1 {
            font: 700 36px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 24px;
            color: var(--text-main);
        }

        .notice {
            padding: 14px 18px;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--neon-green);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .shell {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 24px;
            align-items: start;
        }

        .tabs {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 10px;
            position: sticky;
            top: 20px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .tabs button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            border: 0;
            border-radius: 6px;
            padding: 12px 14px;
            background: transparent;
            color: var(--text-sub);
            font: 700 12px 'Manrope', sans-serif;
            text-align: left;
            cursor: pointer;
            transition: background .15s, color .15s;
        }
        .tabs button:hover {
            background: var(--bg-panel-sub);
            color: var(--text-main);
        }
        .tabs button.active {
            background: rgba(202, 255, 57, 0.12);
            color: var(--neon-green);
            font-weight: 800;
        }

        .pane {
            display: none;
        }
        .pane.active {
            display: block;
            animation: fadeIn .18s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: none; }
        }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .panel h2 {
            font: 700 24px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 6px;
            color: var(--text-main);
        }
        .muted {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .field {
            display: block;
            font: 700 11px/1 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-sub);
            margin-top: 16px;
        }
        .field input, .field select {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            margin-top: 8px;
            border: 1px solid var(--border-input);
            background: var(--bg-input);
            color: var(--text-main);
            border-radius: 6px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus, .field select:focus {
            border-color: var(--neon-green);
            box-shadow: 0 0 0 2px rgba(202, 255, 57, 0.2);
        }
        .field select option {
            background: var(--bg-panel);
            color: var(--text-main);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn {
            min-height: 48px;
            border: 0;
            border-radius: 6px;
            padding: 12px 22px;
            background: var(--neon-green);
            color: #07110d;
            font: 700 13px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            margin-top: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background .15s, transform .1s;
        }
        .btn:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }
        .btn.gray {
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            color: var(--text-main);
        }
        .btn.gray:hover {
            background: var(--border-panel);
        }
        .btn.danger {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }
        .btn.danger:hover {
            background: #39170e;
        }

        .avatar-row {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 16px 0;
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--bg-panel-sub);
            border: 2px solid var(--neon-green);
            display: grid;
            place-items: center;
            font: 700 32px 'Oswald', sans-serif;
            color: var(--neon-green);
        }

        .switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-panel);
        }
        .switch:last-child {
            border: 0;
        }
        .switch input {
            width: 22px;
            height: 22px;
            accent-color: var(--neon-green);
            cursor: pointer;
        }

        .address {
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            padding: 16px;
            border-radius: 8px;
            margin-top: 12px;
        }
        .address b {
            font-size: 13px;
            color: var(--text-main);
        }
        .badge {
            font: 700 10px 'DM Mono', monospace;
            padding: 3px 6px;
            border-radius: 4px;
            background: rgba(202, 255, 57, 0.15);
            color: var(--neon-green);
            border: 1px solid rgba(202, 255, 57, 0.3);
            margin-left: 8px;
            text-transform: uppercase;
        }

        .progress {
            height: 10px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            border-radius: 10px;
            margin: 14px 0;
            overflow: hidden;
        }
        .progress i {
            display: block;
            height: 100%;
            background: var(--neon-green);
            border-radius: 10px;
        }

        .social {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 16px;
        }
        .social button {
            min-height: 48px;
            border: 1px solid var(--border-sub);
            background: var(--bg-panel-sub);
            color: var(--text-sub);
            border-radius: 6px;
            font: 700 11px/1 'Manrope', sans-serif;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .device {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-panel);
        }
        .device:last-child {
            border: 0;
        }

        .guest {
            max-width: 520px;
            margin: 40px auto;
        }

        @media (max-width: 768px) {
            .wrap {
                padding: 20px 14px 40px;
            }
            .shell {
                grid-template-columns: 1fr;
            }
            .tabs {
                position: static;
                flex-direction: row;
                overflow-x: auto;
                padding: 6px;
            }
            .tabs button {
                min-width: max-content;
                padding: 10px 14px;
            }
            .grid {
                grid-template-columns: 1fr;
            }
            .social {
                grid-template-columns: 1fr;
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
        <div class="header-actions">
            <select class="language" id="locale">
                <option value="vi">VI · Tiếng Việt</option>
                <option value="en">EN · English</option>
            </select>
            <a class="back-store" href="{{ route('store.home') }}">Cửa hàng</a>
        </div>
    </div>

    <h1 id="title">Cài đặt</h1>

    @if(session('success'))
        <div class="notice">✓ {{ session('success') }}</div>
    @endif

    @if(! $user)
        <section class="panel guest">
            <h2>Tài khoản</h2>
            <p class="muted">Đăng nhập để lưu sổ địa chỉ, theo dõi đơn hàng và quản lý hồ sơ.</p>
            <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                <a class="btn" href="{{ route('login') }}">ĐĂNG NHẬP</a>
                <a class="btn gray" href="{{ route('register') }}">ĐĂNG KÝ</a>
            </div>
        </section>
    @else
        <div class="shell">
            <aside class="tabs">
                <button class="active" data-tab="profile">◉ &nbsp; Hồ sơ</button>
                <button data-tab="security">⌁ &nbsp; Bảo mật</button>
                <button data-tab="addresses">⌂ &nbsp; Sổ địa chỉ</button>
                <button data-tab="notifications">♧ &nbsp; Thông báo</button>
                <button data-tab="rewards">★ &nbsp; Điểm thưởng</button>
                <button data-tab="danger">! &nbsp; Xóa tài khoản</button>
            </aside>
            <div>
                <section class="pane active" id="profile">
                    <div class="panel">
                        <h2>Hồ sơ</h2>
                        <p class="muted">Thông tin này giúp giao hàng chính xác và nhận quà sinh nhật.</p>
                        <div class="avatar-row">
                            <div class="avatar">
                                @if($user->avatar)
                                    <img class="avatar" src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }}">
                                @else
                                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                @endif
                            </div>
                            <form method="POST" action="{{ route('settings.avatar') }}" enctype="multipart/form-data">
                                @csrf
                                <label class="field" style="margin:0">ẢNH ĐẠI DIỆN
                                    <input type="file" name="avatar" accept="image/*" required style="padding:10px 0">
                                </label>
                                <button class="btn" style="margin-top:10px">TẢI ẢNH LÊN</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('settings.profile') }}">
                            @csrf
                            @method('PUT')
                            <div class="grid">
                                <label class="field">HỌ VÀ TÊN
                                    <input name="name" value="{{ old('name', $user->name) }}" required>
                                </label>
                                <label class="field">SỐ ĐIỆN THOẠI
                                    <input name="phone" value="{{ old('phone', $user->phone) }}">
                                </label>
                                <label class="field">EMAIL
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                </label>
                                <label class="field">GIỚI TÍNH
                                    <select name="gender">
                                        <option value="">Không muốn nêu</option>
                                        <option value="male" @selected($user->gender==='male')>Nam</option>
                                        <option value="female" @selected($user->gender==='female')>Nữ</option>
                                        <option value="other" @selected($user->gender==='other')>Khác</option>
                                    </select>
                                </label>
                                <label class="field">NGÀY SINH
                                    <input type="date" name="birthday" value="{{ $user->birthday }}">
                                </label>
                                <label class="field">NGÔN NGỮ
                                    <select name="locale" id="profileLocale">
                                        <option value="vi" @selected($user->locale==='vi')>Tiếng Việt</option>
                                        <option value="en" @selected($user->locale==='en')>English</option>
                                    </select>
                                </label>
                            </div>
                            <button class="btn" type="submit">LƯU HỒ SƠ</button>
                        </form>
                    </div>
                </section>

                <section class="pane" id="security">
                    <div class="panel">
                        <h2>Đổi mật khẩu</h2>
                        <p class="muted">Xác nhận mật khẩu hiện tại trước khi đặt mật khẩu mới.</p>
                        <form method="POST" action="{{ route('settings.password') }}">
                            @csrf
                            @method('PUT')
                            <label class="field">MẬT KHẨU HIỆN TẠI
                                <input type="password" name="current_password" required>
                            </label>
                            <div class="grid">
                                <label class="field">MẬT KHẨU MỚI
                                    <input type="password" name="password" required>
                                </label>
                                <label class="field">NHẬP LẠI MẬT KHẨU
                                    <input type="password" name="password_confirmation" required>
                                </label>
                            </div>
                            <button class="btn" type="submit">ĐỔI MẬT KHẨU</button>
                        </form>
                    </div>
                    <div class="panel">
                        <h2>Liên kết mạng xã hội</h2>
                        <p class="muted">Cần cấu hình khóa Socialite của từng nhà cung cấp trước khi có thể liên kết.</p>
                        <div class="social">
                            <button type="button">Google · Sắp có</button>
                            <button type="button">Facebook · Sắp có</button>
                            <button type="button">Apple · Sắp có</button>
                        </div>
                    </div>
                    <div class="panel">
                        <h2>Thiết bị đăng nhập</h2>
                        <div class="device">
                            <span><b>Thiết bị hiện tại</b><small class="muted" style="display:block">Đang hoạt động trên trình duyệt này</small></span>
                            <span class="badge">ĐANG DÙNG</span>
                        </div>
                        <form method="POST" action="{{ route('settings.logout-devices') }}">
                            @csrf
                            <label class="field">MẬT KHẨU ĐỂ XÁC NHẬN
                                <input type="password" name="password" required>
                            </label>
                            <button class="btn danger" type="submit">ĐĂNG XUẤT THIẾT BỊ KHÁC</button>
                        </form>
                    </div>
                </section>

                <section class="pane" id="addresses">
                    <div class="panel">
                        <h2>Sổ địa chỉ</h2>
                        <p class="muted">Địa chỉ mặc định sẽ được ưu tiên khi thanh toán.</p>
                        @foreach($addresses as $address)
                            <div class="address">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <b>{{ $address->label }}</b>
                                        @if($address->is_default)
                                            <span class="badge">MẶC ĐỊNH</span>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('settings.addresses.destroy', $address) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button style="border:0;background:none;color:var(--error-text);font:700 11px/1 'DM Mono';cursor:pointer">XÓA</button>
                                    </form>
                                </div>
                                <div class="muted" style="margin-top:8px">
                                    {{ $address->recipient_name }} · {{ $address->phone }}<br>
                                    {{ $address->address_line }}, {{ $address->ward_code }}, {{ $address->province_code }}
                                </div>
                            </div>
                        @endforeach

                        <form method="POST" action="{{ route('settings.addresses.store') }}" style="margin-top:24px">
                            @csrf
                            <div class="grid">
                                <label class="field">NHÃN ĐỊA CHỈ
                                    <input name="label" placeholder="Nhà riêng / Sân bóng" required>
                                </label>
                                <label class="field">NGƯỜI NHẬN
                                    <input name="recipient_name" required>
                                </label>
                                <label class="field">SỐ ĐIỆN THOẠI
                                    <input name="phone" required>
                                </label>
                                <label class="field">TỈNH / THÀNH
                                    <input name="province_code" placeholder="TP. Hồ Chí Minh" required>
                                </label>
                                <label class="field">PHƯỜNG / XÃ
                                    <input name="ward_code" placeholder="Phường Bến Nghé">
                                </label>
                                <label class="field">ĐỊA CHỈ CHI TIẾT
                                    <input name="address_line" required>
                                </label>
                            </div>
                            <label class="field" style="display:flex;align-items:center;gap:10px;cursor:pointer">
                                <input type="checkbox" style="width:18px;height:18px;margin:0" name="is_default" value="1">
                                <span>Đặt làm địa chỉ mặc định</span>
                            </label>
                            <button class="btn" type="submit">THÊM ĐỊA CHỈ</button>
                        </form>
                    </div>
                </section>

                <section class="pane" id="notifications">
                    <div class="panel">
                        <h2>Thông báo</h2>
                        <p class="muted">Bạn tự quyết định loại thông báo muốn nhận.</p>
                        <form method="POST" action="{{ route('settings.notifications') }}">
                            @csrf
                            <div class="switch">
                                <span><b>Khuyến mãi và hàng mới</b><small class="muted" style="display:block">Email về ưu đãi, restock và bộ sưu tập mới</small></span>
                                <input type="checkbox" name="marketing_opt_in" value="1" @checked($user->marketing_opt_in)>
                            </div>
                            <div class="switch">
                                <span><b>Cập nhật hành trình đơn</b><small class="muted" style="display:block">Đơn đã đóng gói, đang giao và giao thành công</small></span>
                                <input type="checkbox" name="order_updates_opt_in" value="1" @checked($user->order_updates_opt_in)>
                            </div>
                            <button class="btn" type="submit">LƯU TÙY CHỌN</button>
                        </form>
                    </div>
                </section>

                <section class="pane" id="rewards">
                    <div class="panel">
                        <h2>Điểm thưởng</h2>
                        <p class="muted">Hạng thành viên hiện tại</p>
                        <h2 style="color:var(--neon-green);font-size:38px;margin:10px 0">{{ $user->loyalty_tier }}</h2>
                        <b style="font-family:'DM Mono';color:var(--text-sub)">{{ number_format($user->loyalty_points) }} điểm</b>
                        <div class="progress">
                            <i style="width:{{ min(100,($user->loyalty_points/500)*100) }}%"></i>
                        </div>
                        <p class="muted">Cần thêm {{ max(0, 500 - $user->loyalty_points) }} điểm để lên hạng Bạc. Điểm được cộng sau khi đơn hoàn thành.</p>
                    </div>
                    <div class="panel">
                        <h2>Đổi voucher</h2>
                        <p class="muted">Voucher sẽ mở khóa khi có đủ điểm. Chưa có giao dịch điểm nào.</p>
                        <button class="btn gray" disabled style="opacity:0.6;cursor:not-allowed">500 ĐIỂM · GIẢM 50.000₫</button>
                    </div>
                </section>

                <section class="pane" id="danger">
                    <div class="panel">
                        <h2 style="color:var(--error-text)">Xóa tài khoản</h2>
                        <p class="muted">Thao tác này không thể hoàn tác. Lịch sử đơn hàng vẫn được lưu nội bộ để đối soát.</p>
                        <form method="POST" action="{{ route('settings.account') }}">
                            @csrf
                            @method('DELETE')
                            <label class="field">MẬT KHẨU ĐỂ XÁC NHẬN
                                <input type="password" name="password" required>
                            </label>
                            <button class="btn danger" type="submit">XÓA TÀI KHOẢN CỦA TÔI</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    @endif
</main>
<script>
    document.querySelectorAll('[data-tab]').forEach(b => b.onclick = () => {
        document.querySelectorAll('[data-tab],.pane').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        document.getElementById(b.dataset.tab).classList.add('active');
    });
    const localeEl = document.getElementById('locale');
    if (localeEl) {
        localeEl.value = '{{ $user?->locale ?? 'vi' }}';
        localeEl.onchange = e => localStorage.setItem('fieldcraft-locale', e.target.value);
    }
</script>
</body>
</html>
