@extends('layouts.admin')

@section('content')
<div class="crumb">DANH MỤC / SẢN PHẨM</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Quản lý sản phẩm</h1>
        <div class="muted">Danh mục giày bóng đá, phụ kiện và quản lý biến thể tồn kho</div>
    </div>
    <a class="btn lime" href="{{ route('admin.products.create') }}">+ THÊM SẢN PHẨM</a>
</div>

<section class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px">ẢNH</th>
                    <th>TÊN SẢN PHẨM / THƯƠNG HIỆU</th>
                    <th>DANH MỤC</th>
                    <th>BIẾN THỂ / KHOẢNG GIÁ / TỒN KHO</th>
                    <th>TRẠNG THÁI</th>
                    <th style="text-align:right">THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            @if($product->images->first())
                                <img class="thumb" src="{{ str_starts_with($product->images->first()->path, 'http') ? $product->images->first()->path : asset('storage/'.$product->images->first()->path) }}" alt="{{ $product->name }}" style="width:46px;height:46px;object-fit:cover;border-radius:4px;border:1px solid var(--border-panel)">
                            @else
                                <div style="width:46px;height:46px;background:var(--bg-panel-sub);border-radius:4px;border:1px solid var(--border-panel);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:0.7rem">Không ảnh</div>
                            @endif
                        </td>
                        <td>
                            <a class="lime-link" href="{{ route('admin.products.edit', $product) }}"><b>{{ $product->name }}</b></a>
                            <div class="muted" style="font-size:0.85rem">{{ $product->brand }}</div>
                        </td>
                        <td><span class="status" style="background:#132a1e;color:var(--lime)">{{ $product->category }}</span></td>
                        <td>
                            <b>{{ $product->variants->count() }} mẫu biến thể</b>
                            <div class="muted" style="font-size:0.82rem;margin-top:2px">
                                <span class="mono">{{ number_format($product->variants->min('price') ?? 0, 0, ',', '.') }}–{{ number_format($product->variants->max('price') ?? 0, 0, ',', '.') }} ₫</span> ·
                                <span class="mono" style="color:var(--lime)">{{ $product->variants->sum('stock') }} tồn</span>
                            </div>
                        </td>
                        <td>
                            <span class="status {{ $product->is_active ? 'completed' : 'cancelled' }}">
                                {{ $product->is_active ? 'Đang bán' : 'Tạm ẩn' }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            <div class="actions" style="justify-content:flex-end">
                                <a class="btn small" href="{{ route('admin.products.edit', $product) }}">SỬA</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Xóa sản phẩm này?')">
                                    @csrf @method('DELETE')
                                    <button class="btn small danger" type="submit">XÓA</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted" style="text-align:center;padding:24px">Chưa có sản phẩm nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:18px">{{ $products->links() }}</div>
</section>
@endsection
