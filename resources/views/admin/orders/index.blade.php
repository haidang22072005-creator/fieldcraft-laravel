@extends('layouts.admin')

@section('content')
<div class="crumb">VẬN HÀNH / ĐƠN HÀNG</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Quản lý đơn hàng</h1>
        <div class="muted">Theo dõi, lọc và xử lý đơn hàng từ khách hàng</div>
    </div>
</div>

<section class="panel">
    <form class="form-inline" method="GET">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã đơn, tên, email, SĐT">
        <select name="status">
            <option value="">Mọi trạng thái</option>
            @foreach(['pending', 'confirmed', 'packing', 'preparing', 'shipping', 'completed', 'cancelled'] as $value)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ \App\Support\UiLabels::orderStatus($value) }}</option>
            @endforeach
        </select>
        <select name="payment_status">
            <option value="">Mọi thanh toán</option>
            @php
                $orderPaymentOptions = [
                    'pending' => 'Chờ thanh toán',
                    'paid' => 'Đã thanh toán',
                    'failed' => 'Thanh toán thất bại',
                    'refund_required' => 'Cần hoàn tiền',
                    'refund_pending' => 'Đang xử lý hoàn tiền',
                    'refunded' => 'Đã hoàn tiền',
                    'refund_failed' => 'Hoàn tiền thất bại',
                ];
            @endphp
            @foreach($orderPaymentOptions as $value => $label)
                <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" title="Từ ngày">
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" title="Đến ngày">
        <button class="btn lime" type="submit">LỌC</button>
        <a class="btn" href="{{ route('admin.orders.index') }}">XOÁ LỌC</a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>MÃ ĐƠN</th>
                    <th>KHÁCH HÀNG / SĐT</th>
                    <th>NGÀY ĐẶT</th>
                    <th>THANH TOÁN</th>
                    <th>TRẠNG THÁI</th>
                    <th>GHN</th>
                    <th style="text-align:right">TỔNG</th>
                    <th style="text-align:right">THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a class="lime-link mono" href="{{ route('admin.orders.show', $order) }}">
                                <b>#{{ $order->number }}</b>
                            </a>
                            <div class="muted" style="font-size:0.8rem">{{ $order->items->count() }} sản phẩm</div>
                        </td>
                        <td>
                            <b>{{ $order->user?->name ?? $order->recipient_name ?? '—' }}</b>
                            <br><span class="muted mono" style="font-size:0.82rem">{{ $order->recipient_phone ?? '—' }}</span>
                        </td>
                        <td class="muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            @php
                                $latestPayment = $order->relationLoaded('payments')
                                    ? $order->payments->sortByDesc('id')->first()
                                    : $order->payments()->latest('id')->first();
                                $refundStatus = $latestPayment?->refund_status;
                                if (!$refundStatus || $refundStatus === 'none') {
                                    $refundStatus = match($order->payment_status) {
                                        'refund_required' => 'required',
                                        'refund_pending' => 'pending',
                                        'refunded' => 'refunded',
                                        'refund_failed' => 'failed',
                                        default => null,
                                    };
                                }
                                $refundBadgeLabels = [
                                    'required' => 'Cần hoàn tiền',
                                    'pending' => 'Đang xử lý hoàn tiền',
                                    'refunded' => 'Đã hoàn tiền',
                                    'failed' => 'Hoàn tiền thất bại',
                                ];
                                $pLabel = $orderPaymentOptions[$order->payment_status] ?? (\App\Support\UiLabels::paymentStatus($order->payment_status) ?: '—');
                            @endphp
                            @if($refundStatus && isset($refundBadgeLabels[$refundStatus]))
                                <span class="status refund-{{ $refundStatus }}">{{ $refundBadgeLabels[$refundStatus] }}</span>
                            @else
                                <span class="status {{ $order->payment_status }}">{{ $pLabel }}</span>
                            @endif
                            <div class="muted" style="font-size:0.75rem;margin-top:2px">{{ \App\Support\UiLabels::paymentMethod($order->payment_method) }}</div>
                        </td>
                        <td>
                            <span class="status {{ $order->status }}">{{ \App\Support\UiLabels::orderStatus($order->status) }}</span>
                        </td>
                        <td>
                            @if($order->ghn_order_code)
                                <b class="mono" style="color:var(--lime);font-size:0.85rem">{{ $order->ghn_order_code }}</b>
                                <div class="muted" style="font-size:0.75rem">{{ \App\Support\UiLabels::ghnStatus($order->shipping_status) }}</div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <b class="mono" style="color:var(--lime)">{{ number_format($order->total, 0, ',', '.') }} ₫</b>
                        </td>
                        <td style="text-align:right">
                            <a class="btn small" href="{{ route('admin.orders.show', $order) }}">CHI TIẾT</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center;padding:24px">Chưa có đơn hàng phù hợp.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:18px">{{ $orders->links() }}</div>
</section>
@endsection
