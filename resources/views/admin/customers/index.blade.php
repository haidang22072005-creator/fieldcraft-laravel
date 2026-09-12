@extends('layouts.admin')

@section('content')
<div class="crumb">KHÁCH HÀNG / DANH SÁCH</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Khách hàng</h1>
        <div class="muted">Quản lý và tra cứu hồ sơ 360 khách hàng trên toàn hệ thống</div>
    </div>
</div>

<section class="panel">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>KHÁCH HÀNG</th>
                    <th>EMAIL</th>
                    <th>ĐƠN ĐÃ MUA</th>
                    <th>THAM GIA</th>
                    <th style="text-align:right">THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:32px;height:32px;border-radius:50%;background:#132a1e;border:1px solid var(--lime);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:var(--lime)">
                                    {{ strtoupper(mb_substr($customer->name, 0, 2)) }}
                                </div>
                                <div>
                                    <a class="lime-link" href="{{ route('admin.customers.show', $customer) }}"><b>{{ $customer->name }}</b></a>
                                </div>
                            </div>
                        </td>
                        <td class="muted">{{ $customer->email }}</td>
                        <td><span class="status mono" style="background:#132a1e;color:var(--lime)">{{ $customer->orders_count }} đơn</span></td>
                        <td class="muted">{{ $customer->created_at->format('d/m/Y') }}</td>
                        <td style="text-align:right">
                            <a class="btn small" href="{{ route('admin.customers.show', $customer) }}">HỒ SƠ 360</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted" style="text-align:center;padding:24px">Chưa có khách hàng đăng ký.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($customers, 'links'))
        <div style="margin-top:18px">{{ $customers->links() }}</div>
    @endif
</section>
@endsection
