<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Thanh toán — Fieldcraft</title>
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

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background: var(--bg-body);
            color: var(--text-main);
            font: 500 14px/1.5 'Manrope', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }

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
            gap: 9px;
            font: 700 26px/1 'Oswald', sans-serif;
            letter-spacing: .02em;
            color: var(--text-main);
        }
        .brand-mark {
            width: 22px;
            height: 22px;
            border-radius: 3px 10px 3px 10px;
            background: var(--neon-green);
            transform: rotate(-20deg);
            box-shadow: inset 0 0 0 5px var(--bg-body);
        }
        .back-cart {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color .2s;
        }
        .back-cart:hover {
            color: var(--neon-green);
        }

        h1 {
            font: 700 38px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin: 0 0 26px;
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
        .alert-banner svg {
            flex-shrink: 0;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 26px;
            align-items: start;
        }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 26px;
        }
        .panel h2 {
            font: 700 20px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin: 0 0 20px;
            color: var(--text-main);
        }

        .fields-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 14px;
        }
        .field {
            display: flex;
            flex-direction: column;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-sub);
        }
        .field.full {
            grid-column: 1 / -1;
        }
        .field.col-3 {
            grid-column: span 1;
        }

        .field-input, .field-select, .field-textarea {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            margin-top: 7px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: 6px;
            color: var(--text-main);
            font: 500 14px/1.4 'Manrope', sans-serif;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field-input::placeholder, .field-textarea::placeholder {
            color: var(--text-muted);
        }
        .field-textarea {
            min-height: 84px;
            resize: vertical;
        }
        .field-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23caff39' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 10px 7px;
            padding-right: 36px;
            cursor: pointer;
        }
        .field-select option {
            background: var(--bg-panel);
            color: var(--text-main);
        }
        .field-input:focus, .field-select:focus, .field-textarea:focus {
            outline: none;
            border-color: var(--neon-green);
            box-shadow: 0 0 0 2px rgba(202, 255, 57, 0.2);
        }
        .field-select:disabled, .field-input:disabled {
            background: #091610;
            border-color: #172d21;
            color: #5d7564;
            cursor: not-allowed;
        }

        .error {
            display: block;
            color: var(--error-text);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            margin-top: 5px;
        }

        /* Payment COD */
        .payment-section {
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid var(--border-panel);
        }
        .pay-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 8px;
            padding: 16px 18px;
            cursor: pointer;
            transition: border-color .2s;
        }
        .pay-card:hover {
            border-color: var(--neon-green);
        }
        .pay-radio {
            margin-top: 3px;
            width: 18px;
            height: 18px;
            accent-color: var(--neon-green);
            cursor: pointer;
            flex-shrink: 0;
        }
        .pay-body strong {
            display: block;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .pay-body span {
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        /* Summary Sidebar */
        aside.panel {
            position: sticky;
            top: 24px;
        }
        .summary-items {
            max-height: 290px;
            overflow-y: auto;
            padding-right: 4px;
            margin-bottom: 18px;
        }
        .summary-items::-webkit-scrollbar {
            width: 4px;
        }
        .summary-items::-webkit-scrollbar-thumb {
            background: var(--border-panel);
            border-radius: 4px;
        }
        .item {
            display: flex;
            gap: 13px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-panel);
        }
        .item:last-child {
            border-bottom: 0;
        }
        .item img, .item-fallback {
            width: 58px;
            height: 58px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--bg-input);
            border: 1px solid var(--border-panel);
            flex-shrink: 0;
        }
        .item-fallback {
            display: grid;
            place-items: center;
            font-size: 20px;
        }
        .item-info {
            flex: 1;
            min-width: 0;
        }
        .item-name {
            font-weight: 800;
            color: var(--text-main);
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-meta {
            color: var(--text-muted);
            font-size: 11px;
            margin-top: 3px;
        }
        .item-price {
            color: var(--neon-green);
            font-weight: 800;
            font-size: 13px;
            margin-top: 3px;
            font-family: 'DM Mono', monospace;
        }

        .coupon-box {
            margin: 18px 0;
            padding: 16px 0;
            border-top: 1px solid var(--border-panel);
            border-bottom: 1px solid var(--border-panel);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 13px;
            color: var(--text-sub);
            font-size: 13px;
        }
        .summary-row strong {
            color: var(--text-main);
            font-family: 'DM Mono', monospace;
            font-size: 14px;
        }
        .summary-row .val-discount {
            color: var(--neon-green);
        }
        .summary-row .val-shipping {
            color: var(--neon-green);
        }
        .summary-row.total {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--border-panel);
            color: var(--text-main);
            font-weight: 800;
            font-size: 15px;
        }
        .summary-row.total strong {
            color: var(--neon-green);
            font-size: 22px;
            font-family: 'DM Mono', monospace;
        }

        .btn-order {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 6px;
            padding: 14px 20px;
            background: var(--neon-green);
            color: #07110d;
            font: 700 15px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-top: 22px;
            cursor: pointer;
            transition: background .15s ease, transform .1s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .btn-order:hover:not(:disabled) {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }
        .btn-order:disabled {
            background: #14251b;
            border: 1px solid #1e392a;
            color: #536c5b;
            cursor: not-allowed;
            transform: none;
        }

        .badge-ghn {
            display: inline-block;
            background: rgba(202, 255, 57, 0.15);
            color: var(--neon-green);
            font: 700 10px/1 'DM Mono', monospace;
            padding: 3px 6px;
            border-radius: 3px;
            border: 1px solid rgba(202, 255, 57, 0.3);
            margin-left: 6px;
        }

        .spinner-dot {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(202, 255, 57, 0.3);
            border-top-color: var(--neon-green);
            border-radius: 50%;
            animation: spin .6s linear infinite;
            vertical-align: middle;
            margin-right: 4px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 860px) {
            .grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            aside.panel {
                position: static;
            }
            .fields-grid {
                grid-template-columns: 1fr;
            }
            .field.col-3 {
                grid-column: 1 / -1;
            }
            .wrap {
                padding: 20px 14px 40px;
            }
            h1 {
                font-size: 32px;
            }
            .field-input, .field-select, .field-textarea, .btn-order {
                min-height: 48px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="top-bar">
        <a class="brand" href="{{ route('store.home') }}"><i class="brand-mark"></i>FIELDCRAFT</a>
        <a class="back-cart" href="{{ route('cart.index') }}">← Quay lại giỏ hàng</a>
    </div>

    <h1>Thanh toán</h1>

    @if($errors->any())
        <div class="alert-banner">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <div id="checkout_error_banner" class="alert-banner" style="display: none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <div id="checkout_error_text"></div>
    </div>

    <form method="POST" action="{{ route('checkout.store') }}" id="checkout_form" class="grid" novalidate>
        @csrf

        {{-- Hidden fields for backend backward compatibility & human-readable snapshot --}}
        <input type="hidden" name="province" id="province" value="{{ old('province', $defaults['province']) }}">
        <input type="hidden" name="district" id="district" value="{{ old('district', $defaults['district']) }}">
        <input type="hidden" name="ward" id="ward" value="{{ old('ward', $defaults['ward']) }}">
        <input type="hidden" name="to_district_id" id="to_district_id" value="{{ old('to_district_id') }}">
        <input type="hidden" name="to_ward_code" id="to_ward_code" value="{{ old('to_ward_code') }}">

        {{-- LEFT COLUMN: Recipient Information & COD --}}
        <div class="left-col">
            <section class="panel">
                <h2>Thông tin nhận hàng</h2>
                <div class="fields-grid">
                    <label class="field full">HỌ VÀ TÊN
                        <input class="field-input" name="recipient_name" id="recipient_name" maxlength="100" required autocomplete="name" placeholder="Nguyễn Văn An" value="{{ old('recipient_name', $defaults['recipient_name']) }}">
                        @error('recipient_name')<span class="error">{{ $message }}</span>@enderror
                    </label>

                    <label class="field">SỐ ĐIỆN THOẠI
                        <input class="field-input" name="recipient_phone" id="recipient_phone" maxlength="10" required inputmode="numeric" autocomplete="tel" placeholder="0912345678" value="{{ old('recipient_phone', $defaults['recipient_phone']) }}">
                        @error('recipient_phone')<span class="error">{{ $message }}</span>@enderror
                    </label>

                    <label class="field">EMAIL
                        <input class="field-input" type="email" name="recipient_email" id="recipient_email" maxlength="255" required autocomplete="email" placeholder="email@example.com" value="{{ old('recipient_email', $defaults['recipient_email']) }}">
                        @error('recipient_email')<span class="error">{{ $message }}</span>@enderror
                    </label>

                    <label class="field col-3">TỈNH / THÀNH PHỐ
                        <select class="field-select" id="province_select" required>
                            <option value="">-- Chọn Tỉnh / Thành --</option>
                        </select>
                        @error('province')<span class="error">{{ $message }}</span>@enderror
                    </label>

                    <label class="field col-3">QUẬN / HUYỆN
                        <select class="field-select" id="district_select" disabled required>
                            <option value="">-- Chọn Quận / Huyện --</option>
                        </select>
                        @error('district')<span class="error">{{ $message }}</span>@enderror
                        @error('to_district_id')<span class="error">{{ $message }}</span>@enderror
                    </label>

                    <label class="field full">PHƯỜNG / XÃ
                        <select class="field-select" id="ward_select" disabled required>
                            <option value="">-- Chọn Phường / Xã --</option>
                        </select>
                        @error('ward')<span class="error">{{ $message }}</span>@enderror
                        @error('to_ward_code')<span class="error">{{ $message }}</span>@enderror
                    </label>

                    <label class="field full">ĐỊA CHỈ CỤ THỂ
                        <input class="field-input" name="address_line" id="address_line" maxlength="255" required autocomplete="street-address" placeholder="Số nhà, tên đường hoặc toà nhà" value="{{ old('address_line', $defaults['address_line']) }}">
                        @error('address_line')<span class="error">{{ $message }}</span>@enderror
                    </label>

                    <label class="field full">GHI CHÚ (KHÔNG BẮT BUỘC)
                        <textarea class="field-textarea" name="note" id="note" maxlength="500" placeholder="Lưu ý giao hàng (ví dụ: giao giờ hành chính, gọi trước khi đến)">{{ old('note') }}</textarea>
                        @error('note')<span class="error">{{ $message }}</span>@enderror
                    </label>
                </div>

                <div class="payment-section">
                    <h2>Phương thức thanh toán</h2>
                    <label class="pay-card">
                        <input class="pay-radio" type="radio" name="payment_method" value="cod" checked>
                        <div class="pay-body">
                            <strong>Thanh toán khi nhận hàng (COD)</strong>
                            <span>Quý khách thanh toán tiền mặt trực tiếp cho nhân viên bưu tá GHN khi nhận bưu phẩm tận nơi.</span>
                        </div>
                    </label>
                    @error('payment_method')<span class="error">{{ $message }}</span>@enderror
                </div>
            </section>
        </div>

        {{-- RIGHT COLUMN: Sticky Order Summary --}}
        <aside class="panel">
            <h2>Đơn hàng của bạn ({{ $items->sum('quantity') }})</h2>

            <div class="summary-items">
                @php($subtotal = 0)
                @foreach($items as $line)
                    @php($variant = $line['variant'])
                    @php($lineTotal = $variant->price * $line['quantity'])
                    @php($subtotal += $lineTotal)
                    @php($path = $variant->product->images->first()?->path)
                    @php($imgUrl = str_starts_with((string)$path, 'http') ? $path : ($path ? asset('storage/'.$path) : ''))
                    <div class="item">
                        @if($imgUrl)
                            <img src="{{ $imgUrl }}" alt="{{ $variant->product->name }}">
                        @else
                            <div class="item-fallback">⚽</div>
                        @endif
                        <div class="item-info">
                            <div class="item-name">{{ $variant->product->name }}</div>
                            <div class="item-meta">{{ $variant->color }} / Size {{ $variant->size }} × {{ $line['quantity'] }}</div>
                            <div class="item-price">{{ number_format($lineTotal, 0, ',', '.') }}₫</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="coupon-box">
                <label class="field">MÃ GIẢM GIÁ
                    <input class="field-input" name="coupon" id="coupon" maxlength="30" value="{{ old('coupon') }}" placeholder="Nhập mã ưu đãi">
                    @error('coupon')<span class="error">{{ $message }}</span>@enderror
                </label>
            </div>

            <div class="summary-row">
                <span>Tạm tính</span>
                <strong id="summary_subtotal" data-amount="{{ $subtotal }}">{{ number_format($subtotal, 0, ',', '.') }}₫</strong>
            </div>

            <div class="summary-row">
                <span>Giảm giá</span>
                <strong class="val-discount" id="summary_discount">0₫</strong>
            </div>

            <div class="summary-row">
                <span>Phí vận chuyển GHN <span class="badge-ghn">EXPRESS</span></span>
                <strong class="val-shipping" id="summary_shipping">Chưa tính</strong>
            </div>

            <div class="summary-row total">
                <span>TỔNG THANH TOÁN</span>
                <strong id="summary_total">{{ number_format($subtotal, 0, ',', '.') }}₫</strong>
            </div>

            <button class="btn-order" id="place_order_btn" type="submit" disabled>
                <span>ĐẶT HÀNG →</span>
            </button>
        </aside>
    </form>
</main>

<script>
    (function () {
        'use strict';

        const subtotal = Number({{ (int)$subtotal }});
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // DOM elements
        const form = document.getElementById('checkout_form');
        const nameInput = document.getElementById('recipient_name');
        const phoneInput = document.getElementById('recipient_phone');
        const emailInput = document.getElementById('recipient_email');
        const addressInput = document.getElementById('address_line');
        const submitBtn = document.getElementById('place_order_btn');

        const provinceSelect = document.getElementById('province_select');
        const districtSelect = document.getElementById('district_select');
        const wardSelect = document.getElementById('ward_select');

        const hiddenProvince = document.getElementById('province');
        const hiddenDistrict = document.getElementById('district');
        const hiddenWard = document.getElementById('ward');
        const hiddenToDistrictId = document.getElementById('to_district_id');
        const hiddenToWardCode = document.getElementById('to_ward_code');

        const shippingDisplay = document.getElementById('summary_shipping');
        const totalDisplay = document.getElementById('summary_total');
        const errorBanner = document.getElementById('checkout_error_banner');
        const errorText = document.getElementById('checkout_error_text');

        // Internal State
        let currentShippingFee = null;
        let isCalculatingFee = false;
        let feeAbortController = null;
        let feeSequence = 0;

        // Formats currency string
        function money(num) {
            return new Intl.NumberFormat('vi-VN').format(Math.max(0, num)) + '₫';
        }

        // Show/hide error banner
        function showError(msg) {
            if (errorBanner && errorText) {
                errorText.textContent = msg;
                errorBanner.style.display = 'flex';
            }
        }
        function clearError() {
            if (errorBanner) {
                errorBanner.style.display = 'none';
            }
        }

        // Validation for enable/disable submit button
        function validateForm() {
            const hasName = Boolean(nameInput && nameInput.value.trim());
            const phoneVal = phoneInput ? phoneInput.value.trim() : '';
            const hasPhone = /^0\d{9}$/.test(phoneVal);
            const emailVal = emailInput ? emailInput.value.trim() : '';
            const hasEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal);
            const hasAddress = Boolean(addressInput && addressInput.value.trim());

            const hasProvince = Boolean(provinceSelect && provinceSelect.value);
            const hasDistrict = Boolean(districtSelect && districtSelect.value);
            const hasWard = Boolean(wardSelect && wardSelect.value);

            const feeReady = currentShippingFee !== null && !isCalculatingFee;

            const isFormValid = hasName && hasPhone && hasEmail && hasAddress &&
                                hasProvince && hasDistrict && hasWard && feeReady;

            if (submitBtn) {
                submitBtn.disabled = !isFormValid;
            }
        }

        // Fetch Provinces on Load
        async function loadProvinces() {
            provinceSelect.innerHTML = '<option value="">Đang tải...</option>';
            provinceSelect.disabled = true;
            clearError();

            try {
                const res = await fetch('{{ route('locations.provinces') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Failed to load provinces');
                const result = await res.json();
                const list = result.data || [];

                provinceSelect.innerHTML = '<option value="">-- Chọn Tỉnh / Thành --</option>';
                list.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.ProvinceID;
                    opt.dataset.name = p.ProvinceName || ('Tỉnh ' + p.ProvinceID);
                    opt.textContent = p.ProvinceName || ('Tỉnh ' + p.ProvinceID);
                    provinceSelect.appendChild(opt);
                });
                provinceSelect.disabled = false;

                // Auto-match if old/default province was provided
                const preselected = hiddenProvince.value;
                if (preselected) {
                    for (let i = 0; i < provinceSelect.options.length; i++) {
                        const opt = provinceSelect.options[i];
                        if (opt.value === preselected || opt.dataset.name === preselected) {
                            provinceSelect.selectedIndex = i;
                            hiddenProvince.value = opt.dataset.name;
                            loadDistricts(opt.value);
                            break;
                        }
                    }
                }
            } catch (e) {
                provinceSelect.innerHTML = '<option value="">-- Chọn Tỉnh / Thành --</option>';
                provinceSelect.disabled = false;
                showError('Không tải được địa chỉ.');
            }
            validateForm();
        }

        // Fetch Districts when Province changes
        async function loadDistricts(provinceId) {
            districtSelect.innerHTML = '<option value="">Đang tải...</option>';
            districtSelect.disabled = true;
            clearError();

            try {
                const res = await fetch(`/locations/districts/${provinceId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Failed to load districts');
                const result = await res.json();
                const list = result.data || [];

                districtSelect.innerHTML = '<option value="">-- Chọn Quận / Huyện --</option>';
                list.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.DistrictID;
                    opt.dataset.name = d.DistrictName || ('Quận ' + d.DistrictID);
                    opt.textContent = d.DistrictName || ('Quận ' + d.DistrictID);
                    districtSelect.appendChild(opt);
                });
                districtSelect.disabled = false;

                // Auto-match if old/default district
                const preselected = hiddenDistrict.value || hiddenToDistrictId.value;
                if (preselected) {
                    for (let i = 0; i < districtSelect.options.length; i++) {
                        const opt = districtSelect.options[i];
                        if (opt.value === preselected || opt.dataset.name === preselected) {
                            districtSelect.selectedIndex = i;
                            hiddenDistrict.value = opt.dataset.name;
                            hiddenToDistrictId.value = opt.value;
                            loadWards(opt.value);
                            break;
                        }
                    }
                }
            } catch (e) {
                districtSelect.innerHTML = '<option value="">-- Chọn Quận / Huyện --</option>';
                districtSelect.disabled = false;
                showError('Không tải được địa chỉ.');
            }
            validateForm();
        }

        // Fetch Wards when District changes
        async function loadWards(districtId) {
            wardSelect.innerHTML = '<option value="">Đang tải...</option>';
            wardSelect.disabled = true;
            clearError();

            try {
                const res = await fetch(`/locations/wards/${districtId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Failed to load wards');
                const result = await res.json();
                const list = result.data || [];

                wardSelect.innerHTML = '<option value="">-- Chọn Phường / Xã --</option>';
                list.forEach(w => {
                    const opt = document.createElement('option');
                    // WardCode MUST stay string
                    opt.value = String(w.WardCode);
                    opt.dataset.name = w.WardName || ('Phường ' + w.WardCode);
                    opt.textContent = w.WardName || ('Phường ' + w.WardCode);
                    wardSelect.appendChild(opt);
                });
                wardSelect.disabled = false;

                // Auto-match if old/default ward
                const preselected = hiddenWard.value || hiddenToWardCode.value;
                if (preselected) {
                    for (let i = 0; i < wardSelect.options.length; i++) {
                        const opt = wardSelect.options[i];
                        if (opt.value === String(preselected) || opt.dataset.name === preselected) {
                            wardSelect.selectedIndex = i;
                            hiddenWard.value = opt.dataset.name;
                            hiddenToWardCode.value = String(opt.value);
                            calculateFee();
                            break;
                        }
                    }
                }
            } catch (e) {
                wardSelect.innerHTML = '<option value="">-- Chọn Phường / Xã --</option>';
                wardSelect.disabled = false;
                showError('Không tải được địa chỉ.');
            }
            validateForm();
        }

        // Calculate Shipping Fee via POST /locations/calculate-fee
        async function calculateFee() {
            const districtId = districtSelect.value;
            const wardCode = wardSelect.value;

            if (!districtId || !wardCode) {
                currentShippingFee = null;
                shippingDisplay.textContent = 'Chưa tính';
                totalDisplay.textContent = money(subtotal);
                validateForm();
                return;
            }

            // Abort previous in-flight request
            if (feeAbortController) {
                feeAbortController.abort();
            }
            feeAbortController = new AbortController();
            const currentSeq = ++feeSequence;

            isCalculatingFee = true;
            shippingDisplay.innerHTML = '<span class="spinner-dot"></span> Đang tải...';
            clearError();
            validateForm();

            try {
                const res = await fetch('{{ route('locations.calculate-fee') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        to_district_id: parseInt(districtId, 10),
                        to_ward_code: String(wardCode), // WardCode strictly string
                    }),
                    signal: feeAbortController.signal,
                });

                if (currentSeq !== feeSequence) return; // Ignore outdated response

                const data = await res.json();
                if (!res.ok || typeof data.shipping_fee !== 'number') {
                    throw new Error(data.message || 'Fee calculation failed');
                }

                currentShippingFee = Number(data.shipping_fee);
                shippingDisplay.textContent = money(currentShippingFee);
                totalDisplay.textContent = money(subtotal + currentShippingFee);
                isCalculatingFee = false;
                clearError();
            } catch (err) {
                if (err.name === 'AbortError') return;
                if (currentSeq !== feeSequence) return;

                currentShippingFee = null;
                isCalculatingFee = false;
                shippingDisplay.textContent = 'Lỗi tính phí';
                totalDisplay.textContent = money(subtotal);
                showError('Không thể tính phí vận chuyển. Vui lòng thử lại.');
            }

            validateForm();
        }

        // Reset helpers on location cascade change
        function resetDistrictAndBelow() {
            districtSelect.innerHTML = '<option value="">-- Chọn Quận / Huyện --</option>';
            districtSelect.disabled = true;
            hiddenDistrict.value = '';
            hiddenToDistrictId.value = '';
            resetWardAndBelow();
        }

        function resetWardAndBelow() {
            wardSelect.innerHTML = '<option value="">-- Chọn Phường / Xã --</option>';
            wardSelect.disabled = true;
            hiddenWard.value = '';
            hiddenToWardCode.value = '';

            // Reset shipping fee
            currentShippingFee = null;
            if (feeAbortController) feeAbortController.abort();
            shippingDisplay.textContent = 'Chưa tính';
            totalDisplay.textContent = money(subtotal);
            clearError();
        }

        // Event listeners
        provinceSelect.addEventListener('change', function () {
            const opt = provinceSelect.selectedOptions[0];
            resetDistrictAndBelow();

            if (opt && opt.value) {
                hiddenProvince.value = opt.dataset.name || opt.text;
                loadDistricts(opt.value);
            } else {
                hiddenProvince.value = '';
            }
            validateForm();
        });

        districtSelect.addEventListener('change', function () {
            const opt = districtSelect.selectedOptions[0];
            resetWardAndBelow();

            if (opt && opt.value) {
                hiddenDistrict.value = opt.dataset.name || opt.text;
                hiddenToDistrictId.value = opt.value;
                loadWards(opt.value);
            } else {
                hiddenDistrict.value = '';
                hiddenToDistrictId.value = '';
            }
            validateForm();
        });

        wardSelect.addEventListener('change', function () {
            const opt = wardSelect.selectedOptions[0];
            if (opt && opt.value) {
                hiddenWard.value = opt.dataset.name || opt.text;
                hiddenToWardCode.value = String(opt.value);
                calculateFee();
            } else {
                hiddenWard.value = '';
                hiddenToWardCode.value = '';
                currentShippingFee = null;
                shippingDisplay.textContent = 'Chưa tính';
                totalDisplay.textContent = money(subtotal);
                validateForm();
            }
        });

        // Field input listeners for immediate validation
        [nameInput, phoneInput, emailInput, addressInput].forEach(el => {
            if (el) {
                el.addEventListener('input', validateForm);
                el.addEventListener('change', validateForm);
            }
        });

        // Submit listener to avoid double submission
        if (form) {
            form.addEventListener('submit', function () {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-dot"></span> ĐANG XỬ LÝ...';
                }
            });
        }

        // Initialize
        loadProvinces();
        validateForm();
    })();
</script>
</body>
</html>
