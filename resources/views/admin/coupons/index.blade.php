@extends('layouts.admin')

@section('content')
<div class="crumb">KHUYẾN MÃI / MÃ GIẢM GIÁ</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Mã giảm giá</h1>
        <div class="muted">Quản lý chiến dịch ưu đãi coupon cho khách hàng</div>
    </div>
    <a class="btn lime" href="{{ route('admin.coupons.create') }}">+ TẠO MÃ MỚI</a>
</div>

<section class="panel">
    <table class="table">
        <thead>
            <tr>
                <th>MÃ VOUCHER</th>
                <th>MỨC GIẢM</th>
                <th>ĐƠN TỐI THIỂU</th>
                <th>LƯỢT DÙNG</th>
                <th>HẾT HẠN</th>
                <th style="text-align:right">THAO TÁC</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coupons as $coupon)
                <tr>
                    <td>
                        <span class="status" style="background:#132a1e;color:var(--lime);font-family:monospace;font-size:0.95rem;font-weight:700;letter-spacing:1px">
                            {{ $coupon->code }}
                        </span>
                    </td>
                    <td>
                        <b style="color:var(--lime)">
                            {{ $coupon->type === 'percent' ? $coupon->value.'%' : number_format($coupon->value, 0, ',', '.').' ₫' }}
                        </b>
                    </td>
                    <td>{{ number_format($coupon->minimum_order_value, 0, ',', '.') }} ₫</td>
                    <td>
                        {{ $coupon->used_count }} {{ $coupon->usage_limit ? '/ '.$coupon->usage_limit : '(không giới hạn)' }}
                    </td>
                    <td class="muted">
                        {{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : 'Vĩnh viễn' }}
                    </td>
                    <td style="text-align:right">
                        <div class="actions" style="justify-content:flex-end">
                            <a class="btn small" href="{{ route('admin.coupons.edit', $coupon) }}">SỬA</a>
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Xoá mã giảm giá này?')">
                                @csrf @method('DELETE')
                                <button class="btn small danger" type="submit">XÓA</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted" style="text-align:center;padding:24px">Chưa có mã giảm giá nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
