@extends('layouts.admin')

@section('content')
<div class="crumb">KHÁCH HÀNG / HỒ SƠ 360 / {{ $customer->name }}</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Hồ sơ khách hàng 360</h1>
        <div class="muted">Toàn bộ lịch sử hành vi mua sắm, giá trị vòng đời và đội bóng liên kết</div>
    </div>
    <a class="btn" href="{{ route('admin.customers.index') }}">← DANH SÁCH KHÁCH HÀNG</a>
</div>

<!-- Customer Profile Summary -->
<div class="panel" style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:18px">
    <div style="display:flex;align-items:center;gap:18px">
        <div style="width:58px;height:58px;border-radius:50%;background:#132a1e;border:2px solid var(--lime);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:900;color:var(--lime);letter-spacing:1px">
            {{ strtoupper(mb_substr($customer->name, 0, 2)) }}
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <b style="font-size:1.25rem;color:var(--text)">{{ $customer->name }}</b>
                <span class="status" style="background:rgba(202,255,57,0.12);color:var(--lime);border-color:rgba(202,255,57,0.3)">Khách hàng</span>
            </div>
            <div class="muted" style="margin-top:4px;font-size:0.88rem;display:flex;gap:16px;flex-wrap:wrap">
                <span>📧 {{ $customer->email }}</span>
                <span>📞 {{ $orders->first()?->recipient_phone ?? $customer->addresses->first()?->phone ?? 'Chưa có SĐT' }}</span>
                <span>🗓️ Tham gia: {{ $customer->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>
    <div>
        <form method="POST" action="{{ route('admin.customers.reset-password', $customer) }}" onsubmit="return confirm('Cấp lại mật khẩu ngẫu nhiên cho khách hàng này?')">
            @csrf
            <button class="btn small danger" type="submit">CẤP LẠI MẬT KHẨU</button>
        </form>
    </div>
</div>

<!-- 360 KPI Grid -->
<div class="kpi-grid" style="margin-bottom:22px;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr))">
    <div class="kpi-card">
        <div class="kpi-label">TỔNG SỐ ĐƠN</div>
        <div class="kpi-value">{{ number_format($customer360['total_orders'] ?? 0) }}</div>
        <div class="kpi-trend neutral">Tất cả thời gian</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">ĐƠN HOÀN TẤT</div>
        <div class="kpi-value" style="color:var(--lime)">{{ number_format($customer360['completed_orders'] ?? 0) }}</div>
        <div class="kpi-trend positive">Giao hàng thành công</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">TỔNG CHI TIÊU HOÀN TẤT</div>
        <div class="kpi-value" style="font-size:1.4rem;color:#38ef7d">{{ number_format($customer360['completed_spend'] ?? 0, 0, ',', '.') }} ₫</div>
        <div class="kpi-trend positive">Doanh thu ròng</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">GIÁ TRỊ ĐƠN TB (AOV)</div>
        <div class="kpi-value" style="font-size:1.4rem">{{ number_format($customer360['average_order_value'] ?? 0, 0, ',', '.') }} ₫</div>
        <div class="kpi-trend neutral">Đơn hoàn tất</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">LẦN MUA CUỐI</div>
        <div class="kpi-value" style="font-size:1.05rem">{{ !empty($customer360['last_purchase']) ? \Carbon\Carbon::parse($customer360['last_purchase'])->format('d/m/Y') : 'Chưa có' }}</div>
        <div class="kpi-trend neutral">{{ !empty($customer360['last_purchase']) ? \Carbon\Carbon::parse($customer360['last_purchase'])->diffForHumans() : '—' }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">THƯƠNG HIỆU YÊU THÍCH</div>
        <div class="kpi-value" style="font-size:1.25rem">{{ $customer360['preferred_brand'] ?? '—' }}</div>
        <div class="kpi-trend neutral">Dựa trên sản phẩm mua</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">SIZE GIÀY THƯỜNG MUA</div>
        <div class="kpi-value" style="color:var(--lime)">{{ !empty($customer360['common_size']) ? 'Size '.$customer360['common_size'] : '—' }}</div>
        <div class="kpi-trend neutral">Cỡ giày phổ biến</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">PHƯƠNG THỨC PHỔ BIẾN</div>
        <div class="kpi-value" style="font-size:1.05rem">{{ !empty($customer360['common_payment_method']) ? \App\Support\UiLabels::paymentMethod($customer360['common_payment_method']) : '—' }}</div>
        <div class="kpi-trend neutral">Kênh thanh toán chính</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">RATING ĐÁNH GIÁ TB</div>
        <div class="kpi-value" style="color:#ffb703">{{ !empty($customer360['approved_review_average']) ? $customer360['approved_review_average'].' ★' : '—' }}</div>
        <div class="kpi-trend neutral">Đánh giá đã duyệt</div>
    </div>
</div>

<!-- Order History -->
<section class="panel" style="margin-bottom:22px">
    <div class="toolbar" style="margin-bottom:14px">
        <b style="font-size:1.1rem">Lịch sử đơn hàng ({{ $orders->count() }})</b>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>MÃ ĐƠN</th>
                    <th>NGÀY TẠO</th>
                    <th>TRẠNG THÁI</th>
                    <th>THANH TOÁN</th>
                    <th>VẬN CHUYỂN</th>
                    <th>SẢN PHẨM</th>
                    <th style="text-align:right">TỔNG TIỀN</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a class="lime-link mono" href="{{ route('admin.orders.show', $order) }}">
                                <b>#{{ $order->number }}</b>
                            </a>
                        </td>
                        <td class="muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                        <td><span class="status {{ $order->status }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span></td>
                        <td>
                            <span class="status {{ $order->payment_status }}">{{ \App\Support\UiLabels::paymentStatus($order->payment_status) }}</span>
                            <div class="muted" style="font-size:0.75rem;margin-top:2px">{{ \App\Support\UiLabels::paymentMethod($order->payment_method) }}</div>
                        </td>
                        <td>
                            <span class="status {{ $order->shipping_status ?? 'muted' }}">{{ \App\Support\UiLabels::ghnStatus($order->shipping_status) }}</span>
                            @if($order->ghn_order_code)
                                <div class="muted mono" style="font-size:0.75rem;margin-top:2px">GHN: {{ $order->ghn_order_code }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:0.85rem">
                                <b>{{ $order->items->count() }} sản phẩm:</b>
                                <ul style="margin:4px 0 0 16px;padding:0;color:var(--muted)">
                                    @foreach($order->items as $item)
                                        <li>{{ $item->product_name }} ({{ $item->color ?? '—' }} / {{ $item->size ?? '—' }}) × {{ $item->quantity }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </td>
                        <td style="text-align:right">
                            <b class="mono" style="color:var(--lime);font-size:1rem">{{ number_format($order->total, 0, ',', '.') }} ₫</b>
                        </td>
                        <td>
                            <a class="btn small" href="{{ route('admin.orders.show', $order) }}">CHI TIẾT</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center;padding:24px">Khách hàng chưa có đơn hàng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- Team Profiles Section -->
<section class="panel" style="margin-bottom:22px">
    <div class="toolbar" style="margin-bottom:14px">
        <b style="font-size:1.1rem">Hồ sơ đội bóng liên kết ({{ count($customer360['teams'] ?? []) }})</b>
    </div>
    <?php $teams = $customer360['teams'] ?? collect(); ?>
    @forelse($teams as $team)
        <div style="background:#09150f;border:1px solid var(--border-panel);border-radius:8px;padding:16px;margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:12px">
                <div>
                    <b style="font-size:1.15rem;color:var(--lime)">{{ $team->team_name }}</b>
                    <div class="muted" style="font-size:0.85rem;margin-top:3px">
                        Đội trưởng: {{ $team->captain ?? 'Chưa đặt' }} · Liên hệ: {{ $team->contact ?? $team->phone ?? '—' }} · {{ $team->members->count() }} thành viên
                    </div>
                </div>
                <div>
                    <a class="btn small lime" href="{{ route('admin.teams.draft-reorder', $team) }}" target="_blank">TẠO ĐƠN NHÁP TỪ ĐỘI</a>
                </div>
            </div>
            @if($team->members->isNotEmpty())
                <div class="table-responsive">
                    <table class="table" style="font-size:0.85rem;margin-top:10px">
                        <thead>
                            <tr>
                                <th>TÊN CẦU THỦ</th>
                                <th>TÊN IN ÁO</th>
                                <th>SỐ ÁO</th>
                                <th>SIZE ÁO</th>
                                <th>GHI CHÚ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($team->members as $member)
                                <tr>
                                    <td><b>{{ $member->player_name }}</b></td>
                                    <td>{{ $member->shirt_name ?? '—' }}</td>
                                    <td><span class="status mono" style="background:#132a1e;color:var(--lime)">#{{ $member->shirt_number ?? '—' }}</span></td>
                                    <td>{{ $member->shirt_size ?? '—' }}</td>
                                    <td class="muted">{{ $member->notes ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="muted" style="margin-top:8px;font-size:0.85rem">Chưa có thành viên trong danh sách đội.</p>
            @endif
        </div>
    @empty
        <p class="muted" style="text-align:center;padding:16px">Khách hàng chưa đăng ký hồ sơ đội bóng nào.</p>
    @endforelse
</section>

<!-- Review History -->
<section class="panel">
    <div class="toolbar" style="margin-bottom:14px">
        <b style="font-size:1.1rem">Đánh giá đã gửi ({{ $customer->reviews->count() }})</b>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>SẢN PHẨM</th>
                    <th>ĐÁNH GIÁ</th>
                    <th>NỘI DUNG</th>
                    <th>PHẢN HỒI FIELDCRAFT</th>
                    <th>TRẠNG THÁI</th>
                    <th>NGÀY</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->reviews as $review)
                    @php
                        $reviewStatusMap = [
                            'pending' => 'Chờ duyệt',
                            'approved' => 'Đã duyệt',
                            'rejected' => 'Từ chối',
                            'hidden' => 'Đã ẩn',
                        ];
                    @endphp
                    <tr>
                        <td><b>{{ $review->product?->name ?? 'Sản phẩm không tồn tại' }}</b></td>
                        <td style="color:#ffb703;white-space:nowrap">{{ str_repeat('★', (int) $review->rating) }}</td>
                        <td>{{ $review->comment }}</td>
                        <td>
                            @if($review->admin_reply)
                                <div style="font-size:0.85rem;color:var(--lime)">{{ $review->admin_reply }}</div>
                            @else
                                <span class="muted" style="font-size:0.8rem">Chưa phản hồi</span>
                            @endif
                        </td>
                        <td>
                            <span class="status {{ $review->status === 'approved' ? 'completed' : ($review->status === 'rejected' ? 'cancelled' : 'pending') }}">
                                {{ $reviewStatusMap[$review->status] ?? ($review->status ? ucfirst($review->status) : '—') }}
                            </span>
                        </td>
                        <td class="muted" style="font-size:0.82rem">{{ $review->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted" style="text-align:center;padding:16px">Khách hàng chưa gửi đánh giá nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
