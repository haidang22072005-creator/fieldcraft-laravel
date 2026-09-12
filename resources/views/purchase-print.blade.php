<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #{{ $order->number }} — Fieldcraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #111827;
            background: #f9fafb;
            padding: 30px 15px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        }

        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #0d1e16;
            color: #caff39;
            font-weight: 700;
            font-size: 13px;
            border: 0;
            padding: 10px 22px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover {
            background: #11261c;
        }
        .link-back {
            color: #4b5563;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .link-back:hover {
            color: #111827;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            padding-bottom: 24px;
            margin-bottom: 28px;
        }
        .brand-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: .02em;
            color: #0d1e16;
        }
        .brand-tagline {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        .invoice-title-block {
            text-align: right;
        }
        .invoice-title {
            font-family: 'Oswald', sans-serif;
            font-size: 26px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #111827;
        }
        .invoice-number {
            font-family: 'DM Mono', monospace;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            margin-top: 4px;
        }
        .invoice-date {
            font-size: 12px;
            color: #6b7280;
            font-family: 'DM Mono', monospace;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 28px;
        }
        .meta-col h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .meta-col p {
            font-size: 13px;
            color: #1f2937;
            margin-bottom: 3px;
        }
        .meta-col p strong {
            color: #111827;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table.items-table th {
            background: #f3f4f6;
            padding: 10px 12px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            color: #374151;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        table.items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            vertical-align: middle;
        }
        .col-qty, .col-price, .col-total {
            text-align: right !important;
            font-family: 'DM Mono', monospace;
        }
        .item-name {
            font-weight: 600;
            color: #111827;
        }
        .item-sku {
            font-size: 11px;
            color: #6b7280;
            font-family: 'DM Mono', monospace;
        }

        .calc-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 36px;
        }
        .calc-box {
            width: 320px;
        }
        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #4b5563;
        }
        .calc-row.total-row {
            border-top: 2px solid #111827;
            padding-top: 12px;
            margin-top: 6px;
            font-weight: 700;
            color: #111827;
        }
        .total-amount {
            font-size: 20px;
            font-family: 'DM Mono', monospace;
            font-weight: 700;
            color: #0d1e16;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-top: 50px;
            text-align: center;
        }
        .sig-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #374151;
            margin-bottom: 60px;
        }
        .sig-sub {
            font-size: 11px;
            color: #9ca3af;
            font-style: italic;
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #e5e7eb;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .invoice-card {
                border: 0;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <a href="{{ route('purchases.show', $order) }}" class="link-back">← Quay lại chi tiết đơn hàng</a>
        <button class="btn-print" type="button" onclick="window.print()">🖨 IN HÓA ĐƠN NÀY</button>
    </div>

    <div class="invoice-card">
        {{-- Invoice Header --}}
        <header class="invoice-header">
            <div>
                <div class="brand-logo">FIELDCRAFT</div>
                <div class="brand-tagline">Hệ thống phân phối giày bóng đá chính hãng</div>
                <div style="font-size:11px;color:#6b7280;margin-top:4px">Hotline: 1900-FC-BOOTS · Website: fieldcraft.vn</div>
            </div>
            <div class="invoice-title-block">
                <h1 class="invoice-title">HÓA ĐƠN BÁN HÀNG</h1>
                <div class="invoice-number">MÃ ĐƠN: #{{ $order->number }}</div>
                <div class="invoice-date">Ngày tạo: {{ $order->created_at?->format('d/m/Y H:i') }}</div>
            </div>
        </header>

        {{-- Recipient & Order Meta --}}
        <div class="meta-grid">
            <div class="meta-col">
                <h3>Thông tin người nhận</h3>
                <p><strong>{{ $order->recipient_name }}</strong></p>
                <p>Điện thoại: {{ $order->recipient_phone }}</p>
                <p>Email: {{ $order->recipient_email }}</p>
                <p>Địa chỉ: {{ $order->address_line }}, {{ $order->ward }}, {{ $order->district }}, {{ $order->province }}</p>
                @if($order->note)
                    <p style="font-style:italic;color:#6b7280">Ghi chú: {{ $order->note }}</p>
                @endif
            </div>

            <div class="meta-col">
                <h3>Thông tin vận chuyển & thanh toán</h3>
                <p>Đơn vị vận chuyển: <strong>GHN Express</strong></p>
                @if($order->ghn_order_code)
                    <p>Mã vận đơn: <strong style="font-family:'DM Mono',monospace">{{ $order->ghn_order_code }}</strong></p>
                @endif
                <p>Phương thức: <strong>{{ \App\Support\UiLabels::paymentMethod($order->payment_method) }}</strong></p>
                <p>Trạng thái thanh toán: <strong>{{ \App\Support\UiLabels::paymentStatus($order->payment_status) }}</strong></p>
                <p>Trạng thái đơn: <strong>{{ \App\Support\UiLabels::orderStatus($order->status) }}</strong></p>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:36px">#</th>
                    <th>Tên sản phẩm</th>
                    <th>SKU / Phân loại</th>
                    <th class="col-qty" style="width:60px">SL</th>
                    <th class="col-price" style="width:110px">Đơn giá</th>
                    <th class="col-total" style="width:120px">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $item)
                    <tr>
                        <td style="color:#9ca3af;font-size:12px">{{ $idx + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->product_name }}</div>
                        </td>
                        <td>
                            <span class="item-sku">{{ $item->sku }}</span>
                            <div style="font-size:11px;color:#6b7280">{{ $item->color }} / Size {{ $item->size }}</div>
                        </td>
                        <td class="col-qty">{{ $item->quantity }}</td>
                        <td class="col-price">{{ number_format($item->unit_price, 0, ',', '.') }}₫</td>
                        <td class="col-total">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}₫</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Financial Calculation --}}
        <div class="calc-section">
            <div class="calc-box">
                <div class="calc-row">
                    <span>Tạm tính hàng hóa:</span>
                    <span style="font-family:'DM Mono',monospace">{{ number_format($order->subtotal, 0, ',', '.') }}₫</span>
                </div>
                <div class="calc-row">
                    <span>Phí vận chuyển (GHN):</span>
                    <span style="font-family:'DM Mono',monospace">+{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span>
                </div>
                @if($order->discount > 0)
                    <div class="calc-row" style="color:#059669">
                        <span>Giảm giá (Coupon):</span>
                        <span style="font-family:'DM Mono',monospace">-{{ number_format($order->discount, 0, ',', '.') }}₫</span>
                    </div>
                @endif
                <div class="calc-row total-row">
                    <span>TỔNG THANH TOÁN:</span>
                    <span class="total-amount">{{ number_format($order->total, 0, ',', '.') }}₫</span>
                </div>
            </div>
        </div>

        {{-- Signatures --}}
        <div class="signatures">
            <div>
                <div class="sig-title">Người lập hóa đơn</div>
                <div class="sig-sub">(Ký và ghi rõ họ tên)</div>
            </div>
            <div>
                <div class="sig-title">Người nhận hàng</div>
                <div class="sig-sub">(Ký xác nhận khi nhận đủ hàng)</div>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="invoice-footer">
            Cảm ơn quý khách đã mua sắm tại Fieldcraft! Mọi thắc mắc xin vui lòng liên hệ hotline hoặc gửi email đến support@fieldcraft.vn.
        </footer>
    </div>
</body>
</html>
