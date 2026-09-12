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

    @if($errors->any())
        <div class="notice" style="background:var(--error-bg);border-color:var(--error-border);color:var(--error-text);margin-bottom:24px">
            @foreach($errors->all() as $err)
                <div>⚠️ {{ $err }}</div>
            @endforeach
        </div>
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
                <button data-tab="vouchers">🎁 &nbsp; Ưu đãi của tôi</button>
                <button data-tab="support">🎧 &nbsp; Hỗ trợ & Khiếu nại</button>
                <button data-tab="rewards">★ &nbsp; Điểm thưởng</button>
                <button data-tab="notifications">♧ &nbsp; Thông báo</button>
                <button data-tab="danger">! &nbsp; Xóa tài khoản</button>
            </aside>
            <div>
                <section class="pane active" id="profile">
                    <div class="panel">
                        <h2>Hồ sơ</h2>
                        <p class="muted">Thông tin này giúp giao hàng chính xác và nhận quà sinh nhật.</p>
                        <div class="avatar-row" style="align-items:flex-start;gap:24px">
                            <div style="text-align:center">
                                <div class="avatar" id="avatarPreviewBox" style="width:96px;height:96px;overflow:hidden;position:relative">
                                    @if($user->avatar)
                                        <img id="avatarPreviewImg" class="avatar" src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover">
                                    @else
                                        <span id="avatarInitials" style="font-size:36px">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
                                    @endif
                                </div>
                                @if($user->avatar)
                                    <form method="POST" action="{{ route('settings.avatar.remove') }}" onsubmit="return confirm('Xóa ảnh đại diện hiện tại?')" style="margin-top:8px">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:var(--error-text);font-size:11px;font-weight:700;cursor:pointer">✕ XÓA ẢNH</button>
                                    </form>
                                @endif
                            </div>
                            <div style="flex:1">
                                <form method="POST" action="{{ route('settings.avatar') }}" enctype="multipart/form-data">
                                    @csrf
                                    <label class="field" style="margin:0">CHỌN ẢNH ĐẠI DIỆN MỚI
                                        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" required style="padding:8px 0" onchange="previewAvatar(this)">
                                    </label>
                                    <div class="muted" style="font-size:11px;margin-top:4px">
                                        Định dạng hỗ trợ: JPG, PNG, WEBP. Dung lượng tối đa: 2MB. Ảnh vuông tỉ lệ 1:1 sẽ hiển thị đẹp nhất.
                                    </div>
                                    @error('avatar')
                                        <div style="color:var(--error-text);font-size:12px;margin-top:4px">{{ $message }}</div>
                                    @enderror
                                    <button class="btn" style="margin-top:12px">CẬP NHẬT ẢNH ĐẠI DIỆN</button>
                                </form>
                            </div>
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

                <section class="pane" id="vouchers">
                    <div class="panel">
                        <h2>Ưu đãi của tôi</h2>
                        <p class="muted">Danh sách mã giảm giá và voucher cá nhân hóa dành riêng cho tài khoản của bạn.</p>

                        <div id="customerVouchersContainer" style="margin-top:20px;display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px">
                            <div class="muted" style="text-align:center;padding:30px">Đang tải mã ưu đãi...</div>
                        </div>
                    </div>
                </section>

                <section class="pane" id="support">
                    <div class="panel">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
                            <div>
                                <h2>Hỗ trợ & Khiếu nại</h2>
                                <p class="muted">Gửi yêu cầu hỗ trợ đơn hàng, sản phẩm, thanh toán và bảo hành Fieldcraft.</p>
                            </div>
                            <button type="button" class="btn" onclick="toggleCreateTicketForm()">
                                + TẠO YÊU CẦU MỚI
                            </button>
                        </div>

                        {{-- Create Ticket Form (collapsible) --}}
                        <div id="createTicketBox" style="display:none;background:var(--bg-panel-sub);border:1px solid var(--border-panel);border-radius:8px;padding:20px;margin-bottom:24px">
                            <h3 style="font:700 16px/1 'Oswald',sans-serif;color:var(--text-main);margin:0 0 14px">GỬI YÊU CẦU HỖ TRỢ CHO FIELDCRAFT</h3>
                            <form id="createTicketForm" onsubmit="submitCustomerTicket(event)">
                                @csrf
                                <div class="grid">
                                    <label class="field">TIÊU ĐỀ YÊU CẦU *
                                        <input type="text" name="subject" required placeholder="VD: Hỏi về thời gian giao hàng đơn..." style="width:100%">
                                    </label>
                                    <label class="field">DANH MỤC *
                                        <select name="category" required>
                                            <option value="order">Đơn hàng</option>
                                            <option value="shipping">Vận chuyển & Giao nhận</option>
                                            <option value="payment">Thanh toán</option>
                                            <option value="refund">Hoàn tiền</option>
                                            <option value="product">Sản phẩm & Size giày</option>
                                            <option value="account">Tài khoản & Đăng nhập</option>
                                            <option value="other">Vấn đề khác</option>
                                        </select>
                                    </label>
                                </div>
                                @php
                                    $userOrders = $user->orders()->latest()->take(20)->get();
                                @endphp
                                @if($userOrders->isNotEmpty())
                                    <label class="field">LIÊN KẾT ĐƠN HÀNG (KHÔNG BẮT BUỘC)
                                        <select name="order_id">
                                            <option value="">-- Không liên kết đơn hàng cụ thể --</option>
                                            @foreach($userOrders as $uo)
                                                <option value="{{ $uo->id }}">Đơn #{{ $uo->number }} · {{ number_format($uo->total) }} ₫ ({{ $uo->created_at->format('d/m/Y') }})</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif
                                <label class="field">NỘI DUNG CHI TIẾT *
                                    <textarea name="message" required rows="4" placeholder="Mô tả cụ thể vấn đề hoặc thắc mắc của bạn để nhân viên hỗ trợ nhanh nhất..." style="width:100%;background:var(--bg-input);border:1px solid var(--border-input);border-radius:6px;padding:10px;color:var(--text-main);resize:vertical"></textarea>
                                </label>
                                <div id="createTicketError" style="display:none;color:var(--error-text);font-size:12px;margin-bottom:12px"></div>
                                <div style="display:flex;gap:10px;justify-content:flex-end">
                                    <button type="button" class="btn gray" onclick="toggleCreateTicketForm()">HỦY</button>
                                    <button type="submit" id="btnSubmitTicket" class="btn">GỬI YÊU CẦU</button>
                                </div>
                            </form>
                        </div>

                        {{-- Ticket List Container --}}
                        <div id="customerTicketsContainer" style="display:flex;flex-direction:column;gap:12px">
                            <div class="muted" style="text-align:center;padding:30px">Đang tải danh sách yêu cầu hỗ trợ...</div>
                        </div>
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

{{-- MODAL: CUSTOMER TICKET CONVERSATION THREAD --}}
<div id="customerTicketModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:12px;max-width:680px;width:100%;max-height:85vh;display:flex;flex-direction:column;padding:24px;position:relative">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--border-panel);padding-bottom:14px;margin-bottom:14px">
            <div>
                <span class="mono" id="modalTicketId" style="font-size:11px;color:var(--neon-green);font-weight:700"></span>
                <h3 id="modalTicketSubject" style="font:700 18px/1.2 'Oswald',sans-serif;color:var(--text-main);margin:4px 0 0"></h3>
                <div id="modalTicketMeta" class="muted" style="font-size:11px;margin-top:4px"></div>
            </div>
            <button type="button" onclick="closeCustomerTicketModal()" style="background:none;border:none;color:var(--text-muted);font-size:22px;cursor:pointer">✕</button>
        </div>

        <div id="modalTicketMessages" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding-right:6px;margin-bottom:16px">
            <div class="muted" style="text-align:center;padding:20px">Đang tải tin nhắn...</div>
        </div>

        <div id="modalReplyWrap" style="border-top:1px solid var(--border-panel);padding-top:14px">
            <form id="customerTicketReplyForm" onsubmit="submitCustomerTicketReply(event)">
                @csrf
                <div style="display:flex;gap:10px">
                    <textarea id="customerReplyInput" name="message" required rows="2" placeholder="Nhập phản hồi của bạn..." style="flex:1;background:var(--bg-input);border:1px solid var(--border-input);border-radius:6px;padding:8px 12px;color:var(--text-main);resize:none"></textarea>
                    <button type="submit" id="btnSendCustomerReply" class="btn" style="margin:0;height:40px;align-self:flex-end">GỬI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Tab switching
    document.querySelectorAll('[data-tab]').forEach(b => b.onclick = () => {
        document.querySelectorAll('[data-tab],.pane').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        const target = document.getElementById(b.dataset.tab);
        if (target) target.classList.add('active');

        if (b.dataset.tab === 'vouchers') {
            fetchCustomerVouchers();
        } else if (b.dataset.tab === 'support') {
            fetchCustomerTickets();
        }
    });

    const localeEl = document.getElementById('locale');
    if (localeEl) {
        localeEl.value = '{{ $user?->locale ?? 'vi' }}';
        localeEl.onchange = e => localStorage.setItem('fieldcraft-locale', e.target.value);
    }

    // Avatar live preview
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) {
                alert('Dung lượng ảnh vượt quá 2MB. Vui lòng chọn ảnh nhỏ hơn.');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const box = document.getElementById('avatarPreviewBox');
                if (box) {
                    box.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
                }
            };
            reader.readAsDataURL(file);
        }
    }

    // Customer Vouchers logic
    let vouchersLoaded = false;
    async function fetchCustomerVouchers() {
        if (vouchersLoaded) return;
        const container = document.getElementById('customerVouchersContainer');
        if (!container) return;

        try {
            const res = await fetch('{{ route('vouchers.index') }}', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            const vouchers = data.data || [];
            vouchersLoaded = true;

            if (vouchers.length === 0) {
                container.innerHTML = `
                    <div style="grid-column:1/-1;text-align:center;padding:40px 20px;background:var(--bg-panel-sub);border:1px dashed var(--border-panel);border-radius:8px">
                        <div style="font-size:32px;margin-bottom:8px">🎁</div>
                        <b style="color:var(--text-main);font-size:15px">Bạn chưa có mã ưu đãi cá nhân nào</b>
                        <p class="muted" style="font-size:12px;margin:6px 0 0">
                            Hãy tiếp tục mua sắm và hoàn thành đơn hàng để tích điểm và mở khóa các voucher đặc quyền từ Fieldcraft.
                        </p>
                    </div>
                `;
                return;
            }

            container.innerHTML = vouchers.map(v => {
                const isPercent = (v.type === 'percent');
                const discountText = isPercent ? `GIẢM ${v.value}%` : `GIẢM ${new Intl.NumberFormat('vi-VN').format(v.value)} ₫`;
                const minOrderText = v.minimum_order_value ? `Đơn từ ${new Intl.NumberFormat('vi-VN').format(v.minimum_order_value)} ₫` : 'Mọi đơn hàng';
                const maxDiscText = v.max_discount ? ` · Tối đa ${new Intl.NumberFormat('vi-VN').format(v.max_discount)} ₫` : '';
                const expiryText = v.expires_at ? `Hạn dùng: ${new Date(v.expires_at).toLocaleDateString('vi-VN')}` : 'Vô thời hạn';

                return `
                    <div style="background:var(--bg-panel-sub);border:1px solid var(--border-sub);border-radius:10px;padding:16px;position:relative;display:flex;flex-direction:column;justify-content:space-between">
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                                <span style="font:700 18px/1 'Oswald',sans-serif;color:var(--neon-green)">${discountText}</span>
                                <span class="badge" style="margin:0">VOUCHER CỦA BẠN</span>
                            </div>
                            <div style="font-size:12px;color:var(--text-sub);margin-bottom:4px">
                                ${minOrderText}${maxDiscText}
                            </div>
                            <div class="muted" style="font-size:11px">
                                🗓️ ${expiryText}
                            </div>
                        </div>
                        <div style="margin-top:14px;padding-top:12px;border-top:1px dashed var(--border-panel);display:flex;justify-content:space-between;align-items:center">
                            <span class="mono" style="font-size:13px;font-weight:700;color:var(--text-main);background:var(--bg-input);padding:4px 8px;border-radius:4px;border:1px solid var(--border-panel)">
                                ${v.code}
                            </span>
                            <button type="button" class="btn" style="margin:0;padding:6px 12px;font-size:11px" onclick="copyVoucherCode('${v.code}')">
                                SAO CHÉP
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } catch (e) {
            container.innerHTML = '<div class="muted" style="text-align:center;padding:20px">Không thể tải mã ưu đãi lúc này.</div>';
        }
    }

    function copyVoucherCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            alert(`✓ Đã sao chép mã ưu đãi "${code}"! Dán mã này tại trang thanh toán.`);
        }).catch(() => {
            prompt('Mã ưu đãi của bạn:', code);
        });
    }

    // Customer Support Tickets logic
    let ticketsLoaded = false;
    let activeCustomerTicketId = null;

    function toggleCreateTicketForm() {
        const box = document.getElementById('createTicketBox');
        if (box) {
            box.style.display = box.style.display === 'none' ? 'block' : 'none';
        }
    }

    async function fetchCustomerTickets() {
        if (ticketsLoaded) return;
        const container = document.getElementById('customerTicketsContainer');
        if (!container) return;

        try {
            const res = await fetch('{{ route('support.tickets.index') }}', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            const tickets = data.data || [];
            ticketsLoaded = true;

            if (tickets.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center;padding:40px 20px;background:var(--bg-panel-sub);border:1px dashed var(--border-panel);border-radius:8px">
                        <div style="font-size:32px;margin-bottom:8px">🎧</div>
                        <b style="color:var(--text-main);font-size:15px">Bạn chưa có yêu cầu hỗ trợ nào</b>
                        <p class="muted" style="font-size:12px;margin:6px 0 0">
                            Nếu bạn gặp bất kỳ vấn đề nào về đơn hàng, chất lượng giày, form chân hoặc hoàn tiền, hãy nhấn nút <b>"+ TẠO YÊU CẦU MỚI"</b>.
                        </p>
                    </div>
                `;
                return;
            }

            const categoryMap = {
                order: 'Đơn hàng',
                shipping: 'Vận chuyển',
                payment: 'Thanh toán',
                refund: 'Hoàn tiền',
                product: 'Sản phẩm',
                account: 'Tài khoản',
                other: 'Khác'
            };
            const statusMap = {
                open: { label: 'Mới mở', class: 'pending' },
                in_progress: { label: 'Đang xử lý', class: 'shipping' },
                resolved: { label: 'Đã giải quyết', class: 'completed' },
                closed: { label: 'Đã đóng', class: 'muted' }
            };

            container.innerHTML = tickets.map(t => {
                const catText = categoryMap[t.category] || t.category;
                const stat = statusMap[t.status] || { label: t.status, class: 'muted' };
                const timeText = t.last_message_at ? new Date(t.last_message_at).toLocaleString('vi-VN') : '';

                return `
                    <div style="background:var(--bg-panel-sub);border:1px solid var(--border-sub);border-radius:8px;padding:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
                        <div style="flex:1;min-width:240px">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <span class="mono" style="font-size:11px;color:var(--neon-green);font-weight:700">#${t.id}</span>
                                <span class="badge" style="margin:0">${catText}</span>
                                <span class="status ${stat.class}" style="font-size:9px;padding:2px 6px">${stat.label}</span>
                            </div>
                            <div style="font-weight:700;font-size:14px;color:var(--text-main);margin-bottom:4px">
                                ${t.subject}
                            </div>
                            <div class="muted" style="font-size:11px">
                                💬 ${t.messages_count || 0} tin nhắn · Cập nhật cuối: ${timeText}
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn" style="margin:0;padding:8px 14px;font-size:11px" onclick="openCustomerTicketModal(${t.id})">
                                XEM CHI TIẾT & TRAO ĐỔI →
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } catch (e) {
            container.innerHTML = '<div class="muted" style="text-align:center;padding:20px">Không thể tải danh sách hỗ trợ lúc này.</div>';
        }
    }

    async function submitCustomerTicket(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSubmitTicket');
        const errBox = document.getElementById('createTicketError');
        errBox.style.display = 'none';
        errBox.textContent = '';
        btn.disabled = true;
        btn.textContent = 'Đang gửi...';

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        if (!payload.order_id) delete payload.order_id;

        const csrfToken = document.querySelector('input[name="_token"]')?.value;
        try {
            const res = await fetch('{{ route('support.tickets.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                alert('✓ Đã gửi yêu cầu hỗ trợ thành công! Đội ngũ Fieldcraft sẽ phản hồi sớm nhất.');
                form.reset();
                toggleCreateTicketForm();
                ticketsLoaded = false;
                fetchCustomerTickets();
            } else {
                let msg = data.message || 'Lỗi khi gửi yêu cầu.';
                if (data.errors) {
                    msg = Object.values(data.errors).flat().join(' ');
                }
                errBox.textContent = msg;
                errBox.style.display = 'block';
            }
        } catch (err) {
            errBox.textContent = 'Không thể kết nối máy chủ.';
            errBox.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = 'GỬI YÊU CẦU';
        }
    }

    async function openCustomerTicketModal(ticketId) {
        activeCustomerTicketId = ticketId;
        const modal = document.getElementById('customerTicketModal');
        const msgContainer = document.getElementById('modalTicketMessages');
        modal.style.display = 'flex';
        msgContainer.innerHTML = '<div class="muted" style="text-align:center;padding:20px">Đang tải cuộc trò chuyện...</div>';

        try {
            const res = await fetch(`/support/tickets/${ticketId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            const ticket = data.data;

            document.getElementById('modalTicketId').textContent = '#' + ticket.id;
            document.getElementById('modalTicketSubject').textContent = ticket.subject;
            document.getElementById('modalTicketMeta').textContent = `Danh mục: ${ticket.category} · Trạng thái: ${ticket.status}` + (ticket.order ? ` · Đơn hàng #${ticket.order.number}` : '');

            const replyWrap = document.getElementById('modalReplyWrap');
            if (ticket.status === 'closed') {
                replyWrap.innerHTML = '<div class="muted" style="text-align:center;font-size:12px;padding:8px">Yêu cầu hỗ trợ này đã đóng.</div>';
            }

            const messages = ticket.messages || [];
            if (messages.length === 0) {
                msgContainer.innerHTML = '<div class="muted" style="text-align:center;padding:20px">Chưa có tin nhắn.</div>';
                return;
            }

            msgContainer.innerHTML = messages.map(m => {
                const isCustomer = (m.sender_role === 'customer');
                const senderTitle = isCustomer ? 'Bạn' : 'Fieldcraft Support';
                const timeStr = m.created_at ? new Date(m.created_at).toLocaleString('vi-VN') : '';

                return `
                    <div style="display:flex;flex-direction:column;align-items:${isCustomer ? 'flex-end' : 'flex-start'}">
                        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">
                            <b>${senderTitle}</b> · ${timeStr}
                        </div>
                        <div style="max-width:80%;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.45;${isCustomer ? 'background:var(--bg-panel-sub);border:1px solid var(--border-sub);color:var(--text-main);' : 'background:#132a1e;border:1px solid var(--neon-green);color:var(--text-main);'}">
                            ${(m.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `;
            }).join('');

            msgContainer.scrollTop = msgContainer.scrollHeight;
        } catch (e) {
            msgContainer.innerHTML = '<div class="muted" style="text-align:center;padding:20px">Không thể tải tin nhắn.</div>';
        }
    }

    function closeCustomerTicketModal() {
        document.getElementById('customerTicketModal').style.display = 'none';
        activeCustomerTicketId = null;
    }

    async function submitCustomerTicketReply(e) {
        e.preventDefault();
        if (!activeCustomerTicketId) return;

        const input = document.getElementById('customerReplyInput');
        const msg = input ? input.value.trim() : '';
        if (!msg) return;

        const btn = document.getElementById('btnSendCustomerReply');
        btn.disabled = true;
        btn.textContent = '...';

        const csrfToken = document.querySelector('input[name="_token"]')?.value;
        try {
            const res = await fetch(`/support/tickets/${activeCustomerTicketId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ message: msg })
            });
            if (res.ok) {
                input.value = '';
                openCustomerTicketModal(activeCustomerTicketId);
                ticketsLoaded = false;
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'Không thể gửi phản hồi.');
            }
        } catch (err) {
            alert('Lỗi kết nối máy chủ.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'GỬI';
        }
    }
</script>
</body>
</html>
