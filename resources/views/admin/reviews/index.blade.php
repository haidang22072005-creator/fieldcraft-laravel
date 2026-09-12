@extends('layouts.admin')

@section('content')
<div class="crumb">CUSTOMER VOICE / REVIEWS</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Đánh giá sản phẩm</h1>
        <div class="muted">Kiểm duyệt và phản hồi ý kiến khách hàng về sản phẩm</div>
    </div>
</div>

<section class="panel">
    @php
        $reviewStatusMap = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'hidden' => 'Đã ẩn',
        ];
    @endphp
    <form class="form-inline" method="GET">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Sản phẩm, khách hàng, nội dung">
        <select name="status">
            <option value="">Mọi trạng thái</option>
            @foreach(['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'hidden' => 'Đã ẩn'] as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" @selected(($filters['status'] ?? '') === $statusKey)>{{ $statusLabel }}</option>
            @endforeach
        </select>
        <button class="btn lime">LỌC</button>
        <a class="btn" href="{{ route('admin.reviews.index') }}">XOÁ LỌC</a>
    </form>

    @forelse($reviews as $review)
        <article style="border-bottom:1px solid var(--border-panel);padding:16px 0">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <b>{{ $review->product?->name ?? 'Sản phẩm không còn tồn tại' }}</b>
                    <div class="muted" style="margin-top:2px">
                        {{ $review->user?->name ?? 'Khách hàng' }} · Đơn {{ $review->order?->number }} ·
                        <span style="color:#ffb703">{{ str_repeat('★', (int) $review->rating) }}</span>
                    </div>
                </div>
                <span class="status {{ $review->status === 'approved' ? 'completed' : ($review->status === 'rejected' ? 'cancelled' : 'pending') }}">
                    {{ $reviewStatusMap[$review->status] ?? ($review->status ? ucfirst($review->status) : 'Khác') }}
                </span>
            </div>
            <p style="margin:10px 0;font-size:0.95rem;color:var(--text-main)">{{ $review->comment }}</p>
            <div class="actions" style="flex-wrap:wrap">
                @foreach(['approved' => 'DUYỆT', 'rejected' => 'TỪ CHỐI', 'hidden' => 'ẨN', 'pending' => 'CHỜ DUYỆT'] as $value => $label)
                    <form method="POST" action="{{ route('admin.reviews.status', $review) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $value }}">
                        <button class="btn small {{ $review->status === $value ? 'lime' : '' }}" type="submit">{{ $label }}</button>
                    </form>
                @endforeach
                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Xóa đánh giá này?')">
                    @csrf @method('DELETE')
                    <button class="btn small danger" type="submit">XÓA</button>
                </form>
            </div>
            <form method="POST" action="{{ route('admin.reviews.reply', $review) }}" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
                @csrf
                <input name="reply" required maxlength="2000" value="{{ $review->admin_reply }}" placeholder="Phản hồi chính thức của FIELDCRAFT" style="flex:1;min-width:240px;padding:8px 12px;background:#09150f;border:1px solid var(--border-panel);border-radius:4px;color:var(--text-main)">
                <button class="btn small lime" type="submit">TRẢ LỜI FIELDCRAFT</button>
            </form>
        </article>
    @empty
        <p class="muted" style="padding:20px 0;text-align:center">Chưa có đánh giá nào.</p>
    @endforelse
    <div style="margin-top:18px">{{ $reviews->links() }}</div>
</section>
@endsection
