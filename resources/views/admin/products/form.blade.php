@extends('layouts.admin')

@section('content')
<div class="crumb">CATALOG / {{ $product->exists ? 'CHỈNH SỬA' : 'TẠO MỚI' }}</div>

<div class="topline" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h1 style="font:700 24px/1 'Oswald', sans-serif;letter-spacing:.04em;margin:0">
        {{ $product->exists ? 'CHỈNH SỬA SẢN PHẨM' : 'THÊM SẢN PHẨM MỚI' }}
    </h1>
    <a class="btn" href="{{ route('admin.products.index') }}">← QUAY LẠI</a>
</div>

<form class="panel form" method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" style="background:var(--bg-panel);border:1px solid var(--border-panel);border-radius:10px;padding:24px">
    @csrf
    @if($product->exists)
        @method('PUT')
    @endif

    <div class="form-grid">
        <div class="field">
            <label>TÊN SẢN PHẨM <span style="color:var(--danger)">*</span></label>
            <input name="name" required value="{{ old('name', $product->name) }}" placeholder="VD: Nike Zoom Mercurial Vapor 16 Pro TF">
        </div>

        <div class="field">
            <label>THƯƠNG HIỆU</label>
            <input name="brand" value="{{ old('brand', $product->brand) }}" placeholder="Nike, adidas, Puma, Mizuno...">
        </div>

        <div class="field">
            <label>DANH MỤC <span style="color:var(--danger)">*</span></label>
            <select name="category" required>
                <option value="Giày đinh" {{ old('category', $product->category) === 'Giày đinh' ? 'selected' : '' }}>Giày đinh</option>
                <option value="Áo đấu" {{ old('category', $product->category) === 'Áo đấu' ? 'selected' : '' }}>Áo đấu</option>
                <option value="Bóng đá" {{ old('category', $product->category) === 'Bóng đá' ? 'selected' : '' }}>Bóng đá</option>
                <option value="Phụ kiện" {{ old('category', $product->category) === 'Phụ kiện' ? 'selected' : '' }}>Phụ kiện</option>
            </select>
        </div>

        <div class="field">
            <label>ẢNH SẢN PHẨM (TỐI ĐA 8 ẢNH, 5MB/ẢNH)</label>
            <input type="file" name="images[]" multiple accept="image/*" style="padding:7px 10px">
        </div>

        <div class="field full">
            <label>MÔ TẢ CHI TIẾT</label>
            <textarea name="description" rows="4" placeholder="Mô tả công nghệ đệm, chất liệu upper, đặc tính kỹ thuật...">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="field full">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->exists ? $product->is_active : true) ? 'checked' : '' }} style="width:auto;margin:0">
                <span style="font-weight:700;color:var(--lime)">ĐANG HIỂN THỊ TRÊN CỬA HÀNG (ACTIVE)</span>
            </label>
        </div>
    </div>

    {{-- Variants Section --}}
    <div class="variants" style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border-panel)">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:12px;flex-wrap:wrap;gap:10px">
            <div>
                <b style="font:700 14px/1 'Oswald', sans-serif;letter-spacing:.06em;color:var(--text-main);text-transform:uppercase">
                    BIẾN THỂ MÀU / SIZE / ĐINH & FORM CHÂN
                </b>
                <div class="muted" style="font-size:11px;margin-top:4px">
                    Mỗi dòng tương ứng với một mã kho riêng (SKU). Hỗ trợ phân loại loại đinh (TF, FG, AG, IC) và form bàn chân (Thon, Tiêu chuẩn, Bè).
                </div>
            </div>
            <button class="btn small lime" type="button" id="addVariant">+ THÊM DÒNG BIẾN THỂ</button>
        </div>

        @php
            $variants = old('variants', $product->variants->toArray() ?: [[
                'sku' => '',
                'color' => 'Trắng / Neon',
                'size' => '40',
                'price' => '',
                'stock' => '10',
                'stud_type' => 'TF',
                'foot_shape' => 'wide',
                'surface_type' => 'Cỏ nhân tạo',
            ]]);
        @endphp

        <div id="variantRows" style="display:flex;flex-direction:column;gap:10px">
            @foreach($variants as $i => $variant)
                <div class="variant-row" style="display:grid;grid-template-columns:140px 120px 80px 120px 80px 140px 140px 36px;gap:8px;align-items:center;background:var(--bg-panel-sub);padding:10px 12px;border:1px solid var(--border-panel);border-radius:6px">
                    <div>
                        <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Mã SKU</span>
                        <input name="variants[{{ $i }}][sku]" placeholder="SKU" value="{{ $variant['sku'] ?? '' }}" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px;font-family:'DM Mono',monospace">
                    </div>
                    <div>
                        <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Màu sắc</span>
                        <input name="variants[{{ $i }}][color]" placeholder="Màu" value="{{ $variant['color'] ?? '' }}" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px">
                    </div>
                    <div>
                        <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Size</span>
                        <input name="variants[{{ $i }}][size]" placeholder="40" value="{{ $variant['size'] ?? '' }}" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px;text-align:center;font-weight:700">
                    </div>
                    <div>
                        <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Giá (₫)</span>
                        <input name="variants[{{ $i }}][price]" type="number" placeholder="1850000" value="{{ $variant['price'] ?? '' }}" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--lime);padding:6px 8px;border-radius:4px;font-size:11px;font-weight:700;font-family:'DM Mono',monospace">
                    </div>
                    <div>
                        <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Tồn kho</span>
                        <input name="variants[{{ $i }}][stock]" type="number" placeholder="10" value="{{ $variant['stock'] ?? 0 }}" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px;text-align:center;font-weight:700;font-family:'DM Mono',monospace">
                    </div>
                    <div>
                        <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Loại đinh</span>
                        <select name="variants[{{ $i }}][stud_type]" style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px">
                            <option value="TF" {{ ($variant['stud_type'] ?? '') === 'TF' ? 'selected' : '' }}>TF (Cỏ nhân tạo)</option>
                            <option value="FG" {{ ($variant['stud_type'] ?? '') === 'FG' ? 'selected' : '' }}>FG (Cỏ tự nhiên)</option>
                            <option value="AG" {{ ($variant['stud_type'] ?? '') === 'AG' ? 'selected' : '' }}>AG (Cỏ dài FIFA)</option>
                            <option value="IC" {{ ($variant['stud_type'] ?? '') === 'IC' ? 'selected' : '' }}>IC (Futsal sàn phẳng)</option>
                        </select>
                    </div>
                    <div>
                        <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Form chân</span>
                        <select name="variants[{{ $i }}][foot_shape]" style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px">
                            <option value="slim" {{ ($variant['foot_shape'] ?? '') === 'slim' ? 'selected' : '' }}>Thon (Slim)</option>
                            <option value="standard" {{ ($variant['foot_shape'] ?? '') === 'standard' ? 'selected' : '' }}>Tiêu chuẩn (Std)</option>
                            <option value="wide" {{ ($variant['foot_shape'] ?? '') === 'wide' ? 'selected' : '' }}>Bè (Wide)</option>
                        </select>
                    </div>
                    <div style="text-align:center">
                        <button type="button" onclick="this.closest('.variant-row').remove()" style="background:transparent;border:0;color:var(--text-muted);cursor:pointer;font-size:16px;line-height:1;margin-top:14px" title="Xóa dòng biến thể">×</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($product->images->count())
        <div class="variants" style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border-panel)">
            <b style="font:700 13px/1 'Oswald', sans-serif;letter-spacing:.04em;color:var(--text-main);text-transform:uppercase">ẢNH ĐANG CÓ</b>
            <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap">
                @foreach($product->images as $image)
                    <div style="position:relative;border:1px solid var(--border-panel);border-radius:8px;overflow:hidden;background:#050c08">
                        <img class="thumb" src="{{ str_starts_with($image->path, 'http') ? $image->path : asset('storage/'.$image->path) }}" alt="" style="width:90px;height:90px;object-fit:cover;display:block">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div style="margin-top:30px;display:flex;gap:12px;align-items:center">
        <button class="btn lime" style="padding:12px 28px;font-size:13px">
            {{ $product->exists ? 'LƯU THAY ĐỔI' : 'TẠO SẢN PHẨM' }} →
        </button>
        <a class="btn" href="{{ route('admin.products.index') }}">HỦY</a>
    </div>
</form>

<script>
let n = {{ count($variants) }};
document.getElementById('addVariant').onclick = () => {
    const defaultSku = 'FC-' + Math.floor(100000 + Math.random() * 900000);
    document.getElementById('variantRows').insertAdjacentHTML('beforeend', `
        <div class="variant-row" style="display:grid;grid-template-columns:140px 120px 80px 120px 80px 140px 140px 36px;gap:8px;align-items:center;background:var(--bg-panel-sub);padding:10px 12px;border:1px solid var(--border-panel);border-radius:6px;animation:fadeIn .15s ease">
            <div>
                <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Mã SKU</span>
                <input name="variants[${n}][sku]" placeholder="SKU" value="${defaultSku}" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px;font-family:'DM Mono',monospace">
            </div>
            <div>
                <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Màu sắc</span>
                <input name="variants[${n}][color]" placeholder="Màu" value="Đen / Bạc" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px">
            </div>
            <div>
                <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Size</span>
                <input name="variants[${n}][size]" placeholder="41" value="41" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px;text-align:center;font-weight:700">
            </div>
            <div>
                <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Giá (₫)</span>
                <input name="variants[${n}][price]" type="number" placeholder="1850000" value="1850000" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--lime);padding:6px 8px;border-radius:4px;font-size:11px;font-weight:700;font-family:'DM Mono',monospace">
            </div>
            <div>
                <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Tồn kho</span>
                <input name="variants[${n}][stock]" type="number" placeholder="10" value="10" required style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px;text-align:center;font-weight:700;font-family:'DM Mono',monospace">
            </div>
            <div>
                <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Loại đinh</span>
                <select name="variants[${n}][stud_type]" style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px">
                    <option value="TF" selected>TF (Cỏ nhân tạo)</option>
                    <option value="FG">FG (Cỏ tự nhiên)</option>
                    <option value="AG">AG (Cỏ dài FIFA)</option>
                    <option value="IC">IC (Futsal sàn phẳng)</option>
                </select>
            </div>
            <div>
                <span class="muted" style="font-size:9px;display:block;text-transform:uppercase;margin-bottom:2px">Form chân</span>
                <select name="variants[${n}][foot_shape]" style="width:100%;border:1px solid var(--border-panel);background:var(--bg-input);color:var(--text-main);padding:6px 8px;border-radius:4px;font-size:11px">
                    <option value="slim">Thon (Slim)</option>
                    <option value="standard">Tiêu chuẩn (Std)</option>
                    <option value="wide" selected>Bè (Wide)</option>
                </select>
            </div>
            <div style="text-align:center">
                <button type="button" onclick="this.closest('.variant-row').remove()" style="background:transparent;border:0;color:var(--text-muted);cursor:pointer;font-size:16px;line-height:1;margin-top:14px" title="Xóa dòng biến thể">×</button>
            </div>
        </div>
    `);
    n++;
};
</script>
@endsection
