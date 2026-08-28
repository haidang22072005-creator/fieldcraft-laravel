@extends('layouts.admin')
@section('content')
<div class="crumb">PROMOTIONS / {{ $coupon->exists ? 'EDIT' : 'CREATE' }}</div>
<div class="topline"><h1>{{ $coupon->exists ? 'Sửa mã giảm giá' : 'Tạo mã giảm giá' }}</h1><a class="btn" href="{{ route('admin.coupons.index') }}">← QUAY LẠI</a></div>
<form class="panel form" method="POST" action="{{ $coupon->exists ? route('admin.coupons.update',$coupon) : route('admin.coupons.store') }}">
    @csrf
    @if($coupon->exists) @method('PUT') @endif
    <div class="form-grid">
        <div class="field"><label>MÃ</label><input name="code" required value="{{ old('code',$coupon->code) }}" placeholder="MESSI10"></div>
        <div class="field"><label>LOẠI GIẢM</label><select name="type"><option value="percent" {{ old('type',$coupon->type)==='percent'?'selected':'' }}>Phần trăm (%)</option><option value="fixed" {{ old('type',$coupon->type)==='fixed'?'selected':'' }}>Số tiền cố định</option></select></div>
        <div class="field"><label>GIÁ TRỊ</label><input name="value" type="number" required value="{{ old('value',$coupon->value) }}"></div>
        <div class="field"><label>ĐƠN TỐI THIỂU</label><input name="minimum_order_value" type="number" value="{{ old('minimum_order_value',$coupon->minimum_order_value) }}"></div>
        <div class="field"><label>GIỚI HẠN LƯỢT DÙNG</label><input name="usage_limit" type="number" min="1" value="{{ old('usage_limit',$coupon->usage_limit) }}"></div>
        <div class="field"><label>GIỚI HẠN MỖI KHÁCH</label><input name="per_user_limit" type="number" min="1" value="{{ old('per_user_limit',$coupon->per_user_limit) }}"></div>
        <div class="field"><label>NGÀY HẾT HẠN</label><input name="expires_at" type="datetime-local" value="{{ old('expires_at',$coupon->expires_at?->format('Y-m-d\TH:i')) }}"></div>
        <label class="field"><input style="width:auto" type="checkbox" name="is_active" value="1" {{ old('is_active',$coupon->exists?$coupon->is_active:true)?'checked':'' }}> Đang kích hoạt</label>
    </div>
    <div style="margin-top:25px"><button class="btn lime">LƯU MÃ GIẢM GIÁ →</button></div>
</form>
@endsection
