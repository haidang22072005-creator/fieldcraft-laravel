<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Giỏ hàng — Fieldcraft</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f4f6f1;color:#07110d;font:500 14px Arial,sans-serif}.wrap{max-width:1120px;margin:auto;padding:30px 20px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px}.brand{font-size:25px;font-weight:900;color:inherit;text-decoration:none}.back{color:#45534a}.layout{display:grid;grid-template-columns:1fr 330px;gap:22px}.panel{background:#fff;border:1px solid #dce3da;border-radius:10px;padding:20px}h1{font-size:36px;margin:0 0 22px}.line{display:grid;grid-template-columns:100px 1fr 150px 120px 36px;gap:16px;align-items:center;padding:16px 0;border-bottom:1px solid #e5e9e3}.line img{width:100px;height:100px;object-fit:cover;border-radius:7px;background:#edf0eb}.meta h2{font-size:15px;margin:0 0 8px}.muted{color:#6c786f;font-size:12px;line-height:1.7}.price{font-weight:800}.qty{display:flex;align-items:center;gap:6px}.qty form{margin:0}.qty button,.remove{border:1px solid #dce3da;background:#fff;border-radius:4px;height:31px;min-width:31px;cursor:pointer}.qty input{width:50px;height:31px;text-align:center;border:1px solid #dce3da;border-radius:4px}.line-total{text-align:right;font-weight:800}.remove{color:#b42318}.warning,.errors{background:#fff1d6;color:#8b4a00;padding:10px;border-radius:5px;margin-top:8px}.summary h2{margin-top:0}.sum{display:flex;justify-content:space-between;font-size:18px;font-weight:900;border-top:1px solid #dce3da;padding-top:18px}.checkout{display:block;text-align:center;margin-top:20px;padding:14px;background:#caff39;color:#07110d;text-decoration:none;border-radius:5px;font-weight:900}.clear{width:100%;border:0;background:none;color:#a21b14;margin-top:13px;cursor:pointer}.empty{text-align:center;padding:70px 20px}.empty a{color:#08783e}.stock{color:#08783e}.stock.bad{color:#b42318;font-weight:700}@media(max-width:800px){.layout{grid-template-columns:1fr}.line{grid-template-columns:75px 1fr auto}.line img{width:75px;height:75px}.qty{grid-column:2}.line-total{grid-column:3;grid-row:2}.remove{grid-column:3;grid-row:1}.panel{padding:14px}}
    </style>
</head>
<body><main class="wrap">
    <div class="top"><a class="brand" href="{{ route('store.home') }}">⚽ FIELDCRAFT</a><a class="back" href="{{ route('store.home') }}">← Tiếp tục mua sắm</a></div>
    <h1>Giỏ hàng <small>({{ $items->sum('quantity') }})</small></h1>
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    @if(session('success'))<div class="warning">{{ session('success') }}</div>@endif
    @if($items->isEmpty())
        <section class="panel empty"><h2>Giỏ hàng đang trống</h2><p>Hãy chọn dụng cụ phù hợp cho trận đấu tiếp theo.</p><a href="{{ route('store.home') }}#shop">Xem sản phẩm</a></section>
    @else
    <div class="layout"><section class="panel">
        @foreach($items as $line)
            @php($variant=$line['variant'])
            @php($path=$variant->product->images->first()?->path)
            <article class="line">
                <img src="{{ str_starts_with((string)$path,'http') ? $path : asset('storage/'.$path) }}" alt="{{ $variant->product->name }}">
                <div class="meta"><h2>{{ $variant->product->name }}</h2><div class="muted">SKU: {{ $variant->sku }}<br>{{ $variant->color }} / Size {{ $variant->size }}</div><div class="price">{{ number_format($variant->price,0,',','.') }}₫</div><div class="stock {{ $line['available'] ? '' : 'bad' }}">Kho: {{ $variant->stock }}{{ $line['available'] ? '' : ' — Hãy giảm số lượng' }}</div></div>
                <div class="qty">
                    <form method="POST" action="{{ route('cart.decrease',$variant) }}">@csrf @method('PATCH')<button aria-label="Giảm">−</button></form>
                    <form method="POST" action="{{ route('cart.update',$variant) }}">@csrf @method('PUT')<input name="quantity" type="number" min="1" max="{{ $variant->stock }}" value="{{ $line['quantity'] }}" onchange="this.form.submit()"></form>
                    <form method="POST" action="{{ route('cart.increase',$variant) }}">@csrf @method('PATCH')<button aria-label="Tăng" {{ $line['quantity'] >= $variant->stock ? 'disabled' : '' }}>+</button></form>
                </div>
                <div class="line-total">{{ number_format($line['subtotal'],0,',','.') }}₫</div>
                <form method="POST" action="{{ route('cart.remove',$variant) }}">@csrf @method('DELETE')<button class="remove" aria-label="Xóa">×</button></form>
            </article>
        @endforeach
    </section><aside class="panel summary"><h2>Tóm tắt đơn hàng</h2><p class="sum"><span>Tạm tính</span><span>{{ number_format($subtotal,0,',','.') }}₫</span></p><p class="muted">Phí vận chuyển và mã giảm giá được tính ở bước thanh toán.</p>
        @if($items->every(fn($line)=>$line['available']))<a class="checkout" href="{{ route('checkout') }}">TIẾN HÀNH THANH TOÁN →</a>@else<div class="warning">Vui lòng sửa các sản phẩm vượt tồn kho.</div>@endif
        <form method="POST" action="{{ route('cart.clear') }}">@csrf @method('DELETE')<button class="clear">Xóa toàn bộ giỏ hàng</button></form>
    </aside></div>
    @endif
</main></body></html>
