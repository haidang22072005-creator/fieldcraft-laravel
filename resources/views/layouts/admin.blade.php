<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Control Center' }} — FIELDCRAFT ADMIN PRO MAX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #07110d;
            --bg-panel: #0d1e16;
            --bg-panel-sub: #11281d;
            --bg-input: #091810;
            --border-panel: #1b3829;
            --border-sub: #234633;
            --lime: #caff39;
            --lime-hover: #b8ec2e;
            --text-main: #ffffff;
            --text-sub: #c9d8cc;
            --text-muted: #8ea395;
            --danger: #ff6b4a;
            --danger-bg: #27110a;
            --danger-border: #522115;
            --warning: #fbbf24;
            --warning-bg: #261f0c;
            --warning-border: #594717;
            --info: #38bdf8;
            --info-bg: rgba(56, 189, 248, 0.12);
            --info-border: rgba(56, 189, 248, 0.3);
            --success: #caff39;
            --success-bg: rgba(202, 255, 57, 0.12);
            --success-border: rgba(202, 255, 57, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--bg-body);
            color: var(--text-main);
            font: 500 13px/1.5 'Manrope', -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }

        /* ── Sidebar ── */
        .side {
            width: 260px;
            background: #050c08;
            border-right: 1px solid var(--border-panel);
            position: fixed;
            inset: 0 auto 0 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--border-panel) transparent;
        }
        .side-brand {
            padding: 22px 20px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font: 700 22px/1 'Oswald', sans-serif;
            letter-spacing: .02em;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-panel);
        }
        .brand-mark {
            width: 22px;
            height: 22px;
            border-radius: 3px 12px 3px 12px;
            background: var(--lime);
            transform: rotate(-20deg);
            box-shadow: inset 0 0 0 5px #050c08;
            flex-shrink: 0;
        }
        .side-pro-tag {
            font: 700 9px/1 'DM Mono', monospace;
            background: var(--lime);
            color: #07110d;
            padding: 3px 6px;
            border-radius: 3px;
            margin-left: auto;
            letter-spacing: .08em;
        }

        .side-nav {
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            flex: 1;
        }
        .nav-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .nav-heading {
            font: 700 10px/1 'DM Mono', monospace;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 6px 10px 4px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            color: var(--text-sub);
            font-size: 12px;
            font-weight: 600;
            transition: all .15s ease;
        }
        .nav-link:hover {
            background: var(--bg-panel-sub);
            color: var(--text-main);
        }
        .nav-link.active {
            background: var(--bg-panel-sub);
            color: var(--lime);
            border-left: 3px solid var(--lime);
            border-radius: 0 6px 6px 0;
            font-weight: 700;
        }
        .nav-icon {
            width: 18px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }
        .nav-link.active .nav-icon {
            color: var(--lime);
        }
        .nav-badge {
            margin-left: auto;
            background: var(--lime);
            color: #07110d;
            font: 700 10px/1 'DM Mono', monospace;
            padding: 3px 6px;
            border-radius: 10px;
        }
        .nav-badge.warning {
            background: var(--warning);
            color: #07110d;
        }

        .side-bottom {
            padding: 14px;
            border-top: 1px solid var(--border-panel);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn-storefront {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            color: var(--text-sub);
            font: 700 11px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            transition: all .15s ease;
        }
        .btn-storefront:hover {
            border-color: var(--lime);
            color: var(--lime);
        }
        .btn-logout {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px;
            background: transparent;
            border: 1px solid var(--danger-border);
            color: var(--danger);
            border-radius: 6px;
            font: 700 11px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            transition: all .15s ease;
        }
        .btn-logout:hover {
            background: var(--danger-bg);
        }

        /* ── Main Layout ── */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
        }
        .admin-topbar {
            height: 64px;
            background: #050c08;
            border-bottom: 1px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            max-width: 600px;
        }
        .search-global {
            width: 100%;
            position: relative;
        }
        .search-global input {
            width: 100%;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            padding: 8px 14px 8px 34px;
            color: var(--text-main);
            font-size: 12px;
            outline: none;
            transition: border-color .15s ease;
        }
        .search-global input:focus {
            border-color: var(--lime);
        }
        .search-global i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-style: normal;
            font-size: 12px;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .live-clock {
            font: 700 11px/1 'DM Mono', monospace;
            color: var(--lime);
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            padding: 6px 12px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .live-dot {
            width: 6px;
            height: 6px;
            background: var(--lime);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--lime);
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        .quick-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-quick {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: var(--text-sub);
            padding: 6px 12px;
            border-radius: 6px;
            font: 700 11px/1 'Oswald', sans-serif;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-quick:hover {
            border-color: var(--lime);
            color: var(--lime);
        }
        .btn-quick.lime {
            background: var(--lime);
            color: #07110d;
            border-color: var(--lime);
        }
        .btn-quick.lime:hover {
            background: var(--lime-hover);
        }

        .admin-content {
            padding: 28px 32px 60px;
            flex: 1;
            max-width: 1700px;
            width: 100%;
        }

        /* ── Common Components ── */
        .crumb {
            font: 700 10px/1 'DM Mono', monospace;
            letter-spacing: .12em;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .topline h1 {
            font: 700 32px/1.1 'Oswald', sans-serif;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: var(--text-main);
            margin: 0;
        }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: border-color .15s ease;
        }
        .panel:hover {
            border-color: var(--border-sub);
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .toolbar-title {
            font: 700 16px/1 'Oswald', sans-serif;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notice {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--lime);
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .errors {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            border: 0;
            border-radius: 6px;
            padding: 9px 16px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            color: var(--text-main);
            font: 700 11px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all .15s ease;
        }
        .btn:hover {
            background: #173726;
            border-color: var(--border-sub);
        }
        .btn.lime {
            background: var(--lime);
            color: #07110d;
            border-color: var(--lime);
        }
        .btn.lime:hover {
            background: var(--lime-hover);
            transform: translateY(-1px);
        }
        .btn.danger {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger);
        }
        .btn.danger:hover {
            background: #3c140d;
        }
        .btn.small {
            padding: 6px 10px;
            font-size: 10px;
        }

        .table-responsive {
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            text-align: left;
            color: var(--text-muted);
            font: 700 10px/1 'DM Mono', monospace;
            letter-spacing: .08em;
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-panel);
            white-space: nowrap;
            text-transform: uppercase;
        }
        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-panel);
            font-size: 12px;
            vertical-align: middle;
            color: var(--text-sub);
        }
        .table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .table tr:last-child td {
            border-bottom: 0;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font: 700 10px/1 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .status.completed, .status.paid, .status.approved {
            background: var(--success-bg);
            color: var(--lime);
            border: 1px solid var(--success-border);
        }
        .status.shipping, .status.confirmed, .status.delivering {
            background: var(--info-bg);
            color: var(--info);
            border: 1px solid var(--info-border);
        }
        .status.pending, .status.pending_payment, .status.unpaid {
            background: var(--warning-bg);
            color: var(--warning);
            border: 1px solid var(--warning-border);
        }
        .status.cancelled, .status.failed, .status.rejected {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }
        .status.muted {
            background: var(--bg-panel-sub);
            color: var(--text-muted);
            border: 1px solid var(--border-panel);
        }

        .muted { color: var(--text-muted); }
        .lime-text { color: var(--lime); }
        .lime-link { color: var(--lime); font-weight: 700; }
        .lime-link:hover { text-decoration: underline; }
        .mono { font-family: 'DM Mono', monospace; }

        .thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 6px;
        }
        .item-image {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
        }
        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        .form-inline {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            align-items: center;
        }
        .form-inline input, .form-inline select {
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            background: var(--bg-input);
            color: var(--text-main);
            padding: 8px 12px;
            font-size: 12px;
            outline: none;
        }
        .form-inline input:focus, .form-inline select:focus {
            border-color: var(--lime);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field label {
            font: 700 11px/1 'DM Mono', monospace;
            text-transform: uppercase;
            color: var(--text-sub);
        }
        .field input, .field textarea, .field select {
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            background: var(--bg-input);
            color: var(--text-main);
            padding: 10px;
            font-size: 13px;
            outline: none;
        }
        .field input:focus, .field textarea:focus, .field select:focus {
            border-color: var(--lime);
        }
        .full { grid-column: 1 / -1; }

        /* Responsive Breakpoints */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: 0;
            color: var(--text-main);
            font-size: 20px;
            cursor: pointer;
        }
        .side-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(2px);
            z-index: 99;
        }

        @media (max-width: 1024px) {
            .side {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }
            .side.open {
                transform: translateX(0);
            }
            .side-overlay.open {
                display: block;
            }
            .main-wrapper {
                margin-left: 0;
            }
            .mobile-menu-btn {
                display: block;
            }
            .admin-topbar {
                padding: 0 16px;
            }
            .admin-content {
                padding: 20px 16px 40px;
            }
        }
    </style>
</head>
<body>
    {{-- Mobile Overlay --}}
    <div class="side-overlay" id="sideOverlay" onclick="toggleSidebar()"></div>

    {{-- Left Navigation Sidebar --}}
    <aside class="side" id="adminSidebar">
        <a class="side-brand" href="{{ route('admin.dashboard') }}">
            <i class="brand-mark"></i>
            <span>FIELDCRAFT</span>
            <span class="side-pro-tag">PRO MAX</span>
        </a>

        @php
            $pendingOrdersCount = \App\Models\Order::where('status', 'pending')->count();
            $pendingReviewsCount = \App\Models\Review::where('status', 'pending')->count();
        @endphp

        <nav class="side-nav">
            {{-- Group 1: TỔNG QUAN --}}
            <div class="nav-group">
                <div class="nav-heading">TỔNG QUAN</div>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && !request()->has('tab') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">▦</span>
                    <span>Bảng điều khiển</span>
                </a>
            </div>

            {{-- Group 2: BÁN HÀNG --}}
            <div class="nav-group">
                <div class="nav-heading">BÁN HÀNG</div>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') && !request()->has('status') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    <span class="nav-icon">□</span>
                    <span>Đơn hàng</span>
                    @if($pendingOrdersCount > 0)
                        <span class="nav-badge">{{ $pendingOrdersCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') && request('status') === 'shipping' ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => 'shipping']) }}">
                    <span class="nav-icon">🚚</span>
                    <span>Vận chuyển</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'finance' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'finance']) }}#finance">
                    <span class="nav-icon">💳</span>
                    <span>Thanh toán</span>
                </a>
            </div>

            {{-- Group 3: SẢN PHẨM --}}
            <div class="nav-group">
                <div class="nav-heading">SẢN PHẨM</div>
                <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <span class="nav-icon">◇</span>
                    <span>Sản phẩm</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'inventory' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'inventory']) }}#inventory">
                    <span class="nav-icon">⊞</span>
                    <span>Kho & Variant</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'restock' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'restock']) }}#restock">
                    <span class="nav-icon">⚡</span>
                    <span>Nhập hàng</span>
                </a>
            </div>

            {{-- Group 4: KHÁCH HÀNG --}}
            <div class="nav-group">
                <div class="nav-heading">KHÁCH HÀNG</div>
                <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                    <span class="nav-icon">👤</span>
                    <span>Khách hàng</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'teams' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'teams']) }}#teams">
                    <span class="nav-icon">🛡</span>
                    <span>Đội bóng</span>
                </a>
            </div>

            {{-- Group 5: CÁ NHÂN HÓA --}}
            <div class="nav-group">
                <div class="nav-heading">CÁ NHÂN HÓA</div>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'customization' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'customization']) }}#customization">
                    <span class="nav-icon">🎽</span>
                    <span>In tên & số</span>
                </a>
            </div>

            {{-- Group 6: TƯƠNG TÁC --}}
            <div class="nav-group">
                <div class="nav-heading">TƯƠNG TÁC</div>
                <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                    <span class="nav-icon">★</span>
                    <span>Đánh giá</span>
                    @if($pendingReviewsCount > 0)
                        <span class="nav-badge warning">{{ $pendingReviewsCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                    <span class="nav-icon">%</span>
                    <span>Mã giảm giá</span>
                </a>
            </div>

            {{-- Group 7: HỆ THỐNG --}}
            <div class="nav-group">
                <div class="nav-heading">HỆ THỐNG</div>
                <a class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}" href="{{ route('admin.accounts.index') }}">
                    <span class="nav-icon">◎</span>
                    <span>Tài khoản</span>
                </a>
            </div>
        </nav>

        {{-- Bottom Action Buttons --}}
        <div class="side-bottom">
            <a class="btn-storefront" href="{{ route('store.home') }}" target="_blank">
                <span>↗</span> Xem cửa hàng
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-logout" type="submit">
                    <span>⏻</span> ĐĂNG XUẤT
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Wrapper --}}
    <div class="main-wrapper">
        {{-- Sticky Top Header --}}
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" type="button" onclick="toggleSidebar()">☰</button>
                <form class="search-global" method="GET" action="{{ route('admin.orders.index') }}">
                    <i>🔍</i>
                    <input type="text" name="q" placeholder="Tìm nhanh đơn hàng #, khách hàng, sản phẩm, SKU... (Nhấn Enter)">
                </form>
            </div>

            <div class="topbar-right">
                <div class="live-clock">
                    <div class="live-dot"></div>
                    <span id="liveClockDisplay">{{ now()->format('d/m/Y H:i:s') }}</span>
                </div>

                <div class="quick-actions">
                    <a class="btn-quick lime" href="{{ route('admin.products.create') }}">+ Sản phẩm</a>
                    <a class="btn-quick" href="{{ route('admin.coupons.create') }}">+ Mã giảm</a>
                </div>
            </div>
        </header>

        {{-- Main Page Content --}}
        <main class="admin-content">
            @if(session('success'))
                <div class="notice">✓ {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="errors">✕ {{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sideOverlay').classList.toggle('open');
        }

        // Live clock
        setInterval(() => {
            const clockEl = document.getElementById('liveClockDisplay');
            if (clockEl) {
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                clockEl.innerText = `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            }
        }, 1000);
    </script>
</body>
</html>
