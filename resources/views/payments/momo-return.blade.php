<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kết quả MoMo — Fieldcraft</title>
<style>body{margin:0;background:#f5f7f1;color:#07110d;font:15px Arial,sans-serif}.box{max-width:520px;margin:12vh auto;background:#fff;border:1px solid #dce3da;border-radius:12px;padding:32px;text-align:center}h1{margin-top:0}.status{font-weight:700;color:#087443}.btn{display:inline-block;margin-top:18px;padding:12px 18px;background:#caff39;color:#07110d;text-decoration:none;border-radius:6px;font-weight:700}</style></head>
<body><main class="box"><h1>Kết quả thanh toán MoMo</h1>
@if($payment)<p>Mã đơn: <strong>{{ $payment->order->number }}</strong></p><p class="status">Trạng thái: {{ $payment->status }}</p><p>Kết quả cuối cùng được xác nhận an toàn qua hệ thống MoMo.</p>
@else<p>Không tìm thấy giao dịch thuộc tài khoản của bạn.</p>@endif
<a class="btn" href="{{ route('purchases') }}">Xem đơn hàng</a></main></body></html>
