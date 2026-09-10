<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thanh toán — Fieldcraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Oswald:wght@500;600&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f5f7f1;color:#07110d;font:500 14px Manrope,sans-serif}.wrap{max-width:1100px;margin:auto;padding:40px 22px}.brand{color:#07110d;text-decoration:none;font:600 28px Oswald,sans-serif}.brand i{display:inline-block;width:20px;height:20px;margin-right:7px;background:#caff39;border-radius:2px 10px 2px 10px;transform:rotate(-20deg)}h1{font:600 42px/.95 Oswald,sans-serif;text-transform:uppercase;margin:38px 0 20px}.grid{display:grid;grid-template-columns:1.15fr .85fr;gap:22px;align-items:start}.panel{background:#fff;border:1px solid #dce3da;border-radius:9px;padding:24px}.panel h2{font:600 19px Oswald,sans-serif;margin:0 0 20px;text-transform:uppercase}.fields{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}.field{display:block;font-size:11px;font-weight:800;margin:0 0 16px}.field.full{grid-column:1/-1}.field input,.field textarea{width:100%;padding:12px;margin-top:7px;border:1px solid #cfd8ce;border-radius:5px;background:#fff;color:#111;font:500 14px Manrope,sans-serif}.field textarea{min-height:82px;resize:vertical}.field input:focus,.field textarea:focus{outline:2px solid #9ecc20;border-color:#9ecc20}.error{display:block;color:#b42318;font-size:11px;margin-top:5px}.item{display:flex;gap:12px;padding:13px 0;border-bottom:1px solid #e7ebe5}.item img{width:58px;height:58px;object-fit:cover;background:#eef2ec;border-radius:5px}.item-info{flex:1}.item-name{font-weight:800}.muted{color:#68736c;font-size:11px;margin-top:4px}.price{font-weight:800;margin-top:4px}.coupon{margin-top:18px}.summary-row{display:flex;justify-content:space-between;margin-top:13px}.total{border-top:1px solid #dce3da;padding-top:15px;font-size:18px;font-weight:800}.pay{display:block;border:1px solid #cfd8ce;border-radius:5px;padding:13px;margin-top:18px;font-weight:800}.btn{width:100%;border:0;border-radius:5px;padding:15px;background:#caff39;color:#07110d;font-weight:800;margin-top:18px;cursor:pointer}.btn:hover{background:#b8ec2e}@media(max-width:760px){.grid,.fields{grid-template-columns:1fr}.wrap{padding:24px 14px}h1{font-size:34px}.field.full{grid-column:auto}}
    </style>
</head>
<body>
<main class="wrap">
    <a class="brand" href="{{ route('store.home') }}"><i></i>FIELDCRAFT</a>
    <h1>Thanh toán</h1>
    <form method="POST" action="{{ route('checkout.store') }}" class="grid">
        @csrf
        <section class="panel">
            <h2>Thông tin người nhận</h2>
            <div class="fields">
                <label class="field full">HỌ VÀ TÊN
                    <input name="recipient_name" maxlength="100" required autocomplete="name" value="{{ old('recipient_name', $defaults['recipient_name']) }}">
                    @error('recipient_name')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label class="field">SỐ ĐIỆN THOẠI
                    <input name="recipient_phone" maxlength="10" required inputmode="numeric" autocomplete="tel" value="{{ old('recipient_phone', $defaults['recipient_phone']) }}">
                    @error('recipient_phone')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label class="field">EMAIL
                    <input type="email" name="recipient_email" maxlength="255" required autocomplete="email" value="{{ old('recipient_email', $defaults['recipient_email']) }}">
                    @error('recipient_email')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label class="field">TỈNH / THÀNH PHỐ
                    <input name="province" maxlength="100" required autocomplete="address-level1" value="{{ old('province', $defaults['province']) }}">
                    @error('province')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label class="field">QUẬN / HUYỆN
                    <input name="district" maxlength="100" required autocomplete="address-level2" value="{{ old('district', $defaults['district']) }}">
                    @error('district')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label class="field full">PHƯỜNG / XÃ
                    <input name="ward" maxlength="100" required autocomplete="address-level3" value="{{ old('ward', $defaults['ward']) }}">
                    @error('ward')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label class="field full">ĐỊA CHỈ CỤ THỂ
                    <input name="address_line" maxlength="255" required autocomplete="street-address" placeholder="Số nhà, tên đường" value="{{ old('address_line', $defaults['address_line']) }}">
                    @error('address_line')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label class="field full">GHI CHÚ (KHÔNG BẮT BUỘC)
                    <textarea name="note" maxlength="500" placeholder="Lưu ý khi giao hàng">{{ old('note') }}</textarea>
                    @error('note')<span class="error">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>
        <aside class="panel">
            <h2>Đơn hàng của bạn</h2>
            @php($subtotal = 0)
            @foreach($items as $line)
                @php($lineTotal = $line['variant']->price * $line['quantity'])
                @php($subtotal += $lineTotal)
                <div class="item">
                    <img src="{{ $line['variant']->product->images->first()?->path }}" alt="{{ $line['variant']->product->name }}">
                    <div class="item-info">
                        <div class="item-name">{{ $line['variant']->product->name }}</div>
                        <div class="muted">{{ $line['variant']->color }} / Size {{ $line['variant']->size }} × {{ $line['quantity'] }}</div>
                        <div class="price">{{ number_format($lineTotal, 0, ',', '.') }}₫</div>
                    </div>
                </div>
            @endforeach
            <label class="field coupon">MÃ GIẢM GIÁ
                <input name="coupon" maxlength="30" value="{{ old('coupon') }}" placeholder="Nhập mã giảm giá">
                @error('coupon')<span class="error">{{ $message }}</span>@enderror
            </label>
            <div class="summary-row"><span>Tạm tính</span><strong>{{ number_format($subtotal, 0, ',', '.') }}₫</strong></div>
            <div class="summary-row"><span>Phí vận chuyển</span><strong>0₫</strong></div>
            <div class="summary-row total"><span>Tổng cộng</span><span>{{ number_format($subtotal, 0, ',', '.') }}₫</span></div>
            <label class="pay"><input type="radio" name="payment_method" value="cod" checked> Thanh toán khi nhận hàng (COD)</label>
            @error('payment_method')<span class="error">{{ $message }}</span>@enderror
            <button class="btn" type="submit">ĐẶT HÀNG →</button>
        </aside>
    </form>
</main>
</body>
</html>
