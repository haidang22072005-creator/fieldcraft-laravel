<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đơn đã mua — Fieldcraft</title>
<style>body{margin:0;background:#f5f7f1;color:#07110d;font:500 14px Arial,sans-serif}.wrap{max-width:850px;margin:auto;padding:38px 20px}.brand{color:#07110d;text-decoration:none;font-size:28px;font-weight:800}.brand i{display:inline-block;width:20px;height:20px;margin-right:7px;background:#caff39;border-radius:2px 10px 2px 10px;transform:rotate(-20deg)}h1{font-size:38px;text-transform:uppercase;margin:38px 0 22px}.order{background:#fff;border:1px solid #dce3da;border-radius:8px;padding:20px;margin-bottom:13px}.head{display:flex;justify-content:space-between;gap:15px}.status{padding:4px 6px;background:#e8f4dd;color:#39742a;border-radius:3px;font-size:10px}.item{margin:12px 0 0;color:#68766d;font-size:11px}.total{font-weight:800}.empty{background:#fff;border:1px dashed #cbd5c8;border-radius:8px;padding:45px;text-align:center;color:#718078}.link{display:inline-block;margin-top:18px;color:#07110d;font-size:11px}.pay-again{border:0;border-radius:5px;padding:11px 15px;background:#caff39;font-weight:800;cursor:pointer}.error{color:#b42318}</style></head>
<body><main class="wrap"><a class="brand" href="{{ route('store.home') }}"><i></i>FIELDCRAFT</a><h1>Những hàng đã mua</h1>
@if($errors->has('payment'))<p class="error">{{ $errors->first('payment') }}</p>@endif
@forelse($orders as $order)
    <section class="order"><div class="head"><div><b>{{ $order->number }}</b><div style="color:#718078;font-size:10px;margin-top:3px">{{ $order->created_at->format('d/m/Y H:i') }}</div></div><div><span class="status">{{ $order->status }}</span><div class="total" style="margin-top:8px">{{ number_format($order->total,0,',','.') }}₫</div></div></div>
    @foreach($order->items as $item)<div class="item">{{ $item->product_name }} · {{ $item->color }} · Size {{ $item->size }} × {{ $item->quantity }}</div>@endforeach
    @php($lastPayment = $order->payments->sortByDesc('id')->first())
    @if($order->payment_method === 'momo' && $lastPayment && in_array($lastPayment->status, ['failed','cancelled'], true))
        <form method="POST" action="{{ route('momo.retry', $order) }}">@csrf<button class="pay-again" type="submit">Thanh toán lại qua MoMo →</button></form>
    @endif
    </section>
@empty<div class="empty">Bạn chưa có đơn hàng nào.<br><a class="link" href="{{ route('store.home') }}">Đi mua sắm →</a></div>@endforelse
<a class="link" href="{{ route('settings') }}">← Quay lại cài đặt</a></main></body></html>
