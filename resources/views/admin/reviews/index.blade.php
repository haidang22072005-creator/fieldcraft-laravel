@extends('layouts.admin')

@section('content')
<div class="crumb">CUSTOMER VOICE / REVIEWS</div>
<div class="topline"><h1>Đánh giá sản phẩm</h1></div>
<section class="panel">
    <form class="form-inline" method="GET"><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Sản phẩm, khách hàng, nội dung"><select name="status"><option value="">Mọi trạng thái</option>@foreach(['pending','approved','rejected','hidden'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '')===$status)>{{ $status }}</option>@endforeach</select><button class="btn lime">LỌC</button><a class="btn" href="{{ route('admin.reviews.index') }}">XOÁ LỌC</a></form>
    @forelse($reviews as $review)
        <article style="border-bottom:1px solid #edf1ec;padding:14px 0">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <b>{{ $review->product?->name ?? 'Sản phẩm không còn tồn tại' }}</b>
                    <div class="muted">{{ $review->user?->name ?? 'Khách hàng' }} · {{ $review->order?->number }} · {{ str_repeat('★', (int) $review->rating) }}</div>
                </div>
                <span class="status">{{ $review->status }}</span>
            </div>
            <p style="margin:8px 0">{{ $review->comment }}</p>
            <div class="actions" style="flex-wrap:wrap">
                @foreach(['approved' => 'DUYỆT', 'rejected' => 'TỪ CHỐI', 'hidden' => 'ẨN', 'pending' => 'CHỜ DUYỆT'] as $value => $label)
                    <form method="POST" action="{{ route('admin.reviews.status', $review) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $value }}">
                        <button class="btn small" type="submit">{{ $label }}</button>
                    </form>
                @endforeach
                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Xóa đánh giá này?')">
                    @csrf @method('DELETE')
                    <button class="btn small danger" type="submit">XÓA</button>
                </form>
            </div>
            <form method="POST" action="{{ route('admin.reviews.reply', $review) }}" style="display:flex;gap:8px;margin-top:9px;flex-wrap:wrap">
                @csrf
                <input name="reply" required maxlength="2000" value="{{ $review->admin_reply }}" placeholder="Phản hồi chính thức của FIELDCRAFT" style="flex:1;min-width:240px;padding:8px;border:1px solid #e0e7de;border-radius:4px">
                <button class="btn small lime" type="submit">TRẢ LỜI FIELDCRAFT</button>
            </form>
        </article>
    @empty
        <p class="muted">Chưa có đánh giá nào.</p>
    @endforelse
    <div style="margin-top:18px">{{ $reviews->links() }}</div>
</section>
@endsection
