@extends('layouts.admin')

@section('content')
@php
    $summary = $report['summary'];
    $paymentLabels = ['cod' => 'COD', 'momo' => 'MoMo', 'bank_qr' => 'Bank QR', 'payos' => 'payOS', 'online' => 'Online'];
    $maxCategoryRevenue = max(1, (int) collect($report['categories'])->max('revenue'));
    $maxDailyRevenue = max(1, (int) collect($report['daily'])->max('revenue'));
@endphp
<div class="crumb">PHÂN TÍCH / BÁO CÁO & PHÂN TÍCH</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">BÁO CÁO & PHÂN TÍCH</h1>
        <div class="muted">Doanh thu hợp lệ, đơn hàng, phương thức thanh toán và danh mục sản phẩm</div>
    </div>
</div>

<section class="panel" style="margin-bottom:18px">
    <form class="form-inline" method="GET">
        <label class="muted" for="report-date-from">Từ ngày</label>
        <input id="report-date-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        <label class="muted" for="report-date-to">Đến ngày</label>
        <input id="report-date-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        <button class="btn lime" type="submit">CẬP NHẬT</button>
        <a class="btn" href="{{ route('admin.reports.index') }}">XOÁ LỌC</a>
    </form>
</section>

<section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px">
    @foreach([
        ['label' => 'Doanh thu hợp lệ', 'value' => number_format($summary['valid_revenue'], 0, ',', '.').' ₫'],
        ['label' => 'Đơn hoàn tất hợp lệ', 'value' => number_format($summary['completed_orders'])],
        ['label' => 'Tổng đơn trong kỳ', 'value' => number_format($summary['total_orders'])],
        ['label' => 'Giá trị đơn trung bình', 'value' => number_format($summary['average_order_value'], 0, ',', '.').' ₫'],
    ] as $kpi)
        <div class="panel">
            <div class="muted" style="font-size:.8rem;text-transform:uppercase">{{ $kpi['label'] }}</div>
            <div class="mono" style="font-size:1.45rem;color:var(--lime);margin-top:8px">{{ $kpi['value'] }}</div>
        </div>
    @endforeach
</section>

<section class="panel report-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
        <div>
            <h2 style="margin:0">Số liệu vận hành</h2>
            <div class="muted" style="margin-top:4px">Đơn huỷ, thanh toán thất bại và hoàn tiền không được tính vào doanh thu hợp lệ.</div>
        </div>
        <div style="display:flex;gap:8px">
            <button class="btn small lime" type="button" data-report-tab="tables">BẢNG SỐ LIỆU</button>
            <button class="btn small" type="button" data-report-tab="charts">BIỂU ĐỒ</button>
        </div>
    </div>

    <div data-report-view="tables">
        <div class="table-responsive" style="margin-bottom:18px">
            <table class="table">
                <thead><tr><th>PHƯƠNG THỨC</th><th>ĐƠN HỢP LỆ</th><th style="text-align:right">DOANH THU</th></tr></thead>
                <tbody>
                @foreach($report['payment_methods'] as $method => $row)
                    <tr><td>{{ $paymentLabels[$method] ?? \App\Support\UiLabels::paymentMethod($method) }}</td><td>{{ number_format($row['order_count']) }}</td><td style="text-align:right" class="mono">{{ number_format($row['revenue'], 0, ',', '.') }} ₫</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="table-responsive" style="margin-bottom:18px">
            <table class="table">
                <thead><tr><th>DANH MỤC</th><th>SỐ LƯỢNG</th><th style="text-align:right">GIÁ TRỊ SẢN PHẨM</th></tr></thead>
                <tbody>
                @forelse($report['categories'] as $row)
                    <tr><td>{{ $row['category'] }}</td><td>{{ number_format($row['quantity']) }}</td><td style="text-align:right" class="mono">{{ number_format($row['revenue'], 0, ',', '.') }} ₫</td></tr>
                @empty
                    <tr><td colspan="3" class="muted">Chưa có dữ liệu danh mục.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @foreach(['daily' => 'Doanh thu theo ngày', 'monthly' => 'Doanh thu theo tháng', 'yearly' => 'Doanh thu theo năm'] as $key => $title)
            <div class="table-responsive" style="margin-bottom:18px">
                <h3>{{ $title }}</h3>
                <table class="table">
                    <thead><tr><th>KỲ</th><th>ĐƠN HỢP LỆ</th><th style="text-align:right">DOANH THU</th></tr></thead>
                    <tbody>
                    @forelse($report[$key] as $row)
                        <tr><td class="mono">{{ $row['period'] }}</td><td>{{ number_format($row['order_count']) }}</td><td style="text-align:right" class="mono">{{ number_format($row['revenue'], 0, ',', '.') }} ₫</td></tr>
                    @empty
                        <tr><td colspan="3" class="muted">Chưa có dữ liệu trong khoảng thời gian này.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <div data-report-view="charts" hidden>
        <h3>Doanh thu theo danh mục</h3>
        @forelse($report['categories'] as $row)
            <div style="margin:12px 0">
                <div style="display:flex;justify-content:space-between;gap:12px"><span>{{ $row['category'] }}</span><span class="mono">{{ number_format($row['revenue'], 0, ',', '.') }} ₫</span></div>
                <div style="height:10px;background:#183023;border-radius:99px;overflow:hidden;margin-top:5px"><div style="width:{{ round($row['revenue'] / $maxCategoryRevenue * 100, 2) }}%;height:100%;background:var(--lime)"></div></div>
            </div>
        @empty
            <div class="muted">Chưa có dữ liệu danh mục.</div>
        @endforelse
        <h3 style="margin-top:24px">Doanh thu theo ngày</h3>
        @forelse($report['daily'] as $row)
            <div style="margin:12px 0">
                <div style="display:flex;justify-content:space-between;gap:12px"><span class="mono">{{ $row['period'] }}</span><span class="mono">{{ number_format($row['revenue'], 0, ',', '.') }} ₫</span></div>
                <div style="height:10px;background:#183023;border-radius:99px;overflow:hidden;margin-top:5px"><div style="width:{{ round($row['revenue'] / $maxDailyRevenue * 100, 2) }}%;height:100%;background:#61a7ff"></div></div>
            </div>
        @empty
            <div class="muted">Chưa có dữ liệu theo ngày.</div>
        @endforelse
    </div>
</section>

<style>
    .report-panel { overflow: hidden; }
    .report-panel .table-responsive { overflow-x: auto; }
    @media (max-width: 720px) {
        .report-panel .table { min-width: 560px; }
        .report-panel .form-inline { align-items: stretch; }
    }
</style>
<script>
    document.querySelectorAll('[data-report-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-report-view]').forEach(function (view) { view.hidden = view.dataset.reportView !== button.dataset.reportTab; });
            document.querySelectorAll('[data-report-tab]').forEach(function (tab) { tab.classList.toggle('lime', tab === button); });
        });
    });
</script>
@endsection
