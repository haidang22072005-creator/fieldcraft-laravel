@extends('layouts.admin')

@section('content')
<div class="crumb">HỆ THỐNG / NGƯỜI DÙNG & PHÂN QUYỀN</div>
<div class="topline">
    <div>
        <h1 style="margin-bottom:4px">Người dùng & Phân quyền</h1>
        <div class="muted">Quản trị tài khoản, vai trò, trạng thái và hồ sơ khách hàng</div>
    </div>
</div>

@if(auth()->user()->role === 'super-admin')
    <section class="panel" style="margin-bottom:20px">
        <b style="font-size:1.05rem">Tạo tài khoản quản trị viên mới</b>
        <form class="form-inline" method="POST" action="{{ route('admin.accounts.store') }}" style="margin-top:14px">
            @csrf
            <input name="name" required placeholder="Họ và tên">
            <input type="email" name="email" required placeholder="Email quản trị">
            <input type="password" name="password" required minlength="8" placeholder="Mật khẩu (tối thiểu 8 ký tự)">
            <input type="password" name="password_confirmation" required placeholder="Nhập lại mật khẩu">
            <button class="btn lime" type="submit">TẠO ADMIN</button>
        </form>
    </section>
@endif

<section class="panel">
    @php
        $roleMap = [
            'super-admin' => 'Quản trị tối cao',
            'admin' => 'Quản trị viên',
            'customer' => 'Khách hàng',
        ];
    @endphp
    <form class="form-inline" method="GET">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo tên, email hoặc SĐT">
        <select name="role">
            <option value="">Mọi phân quyền</option>
            @foreach(['super-admin' => 'Quản trị tối cao', 'admin' => 'Quản trị viên', 'customer' => 'Khách hàng'] as $roleKey => $roleLabel)
                <option value="{{ $roleKey }}" @selected(($filters['role'] ?? '') === $roleKey)>{{ $roleLabel }}</option>
            @endforeach
        </select>
        <button class="btn lime">LỌC</button>
        <a class="btn" href="{{ route('admin.accounts.index') }}">XOÁ LỌC</a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>TÀI KHOẢN</th>
                    <th>LIÊN HỆ / TRẠNG THÁI</th>
                    <th>VAI TRÒ</th>
                    <th>ĐƠN HÀNG</th>
                    <th>CHI TIÊU HOÀN TẤT</th>
                    <th>THAM GIA</th>
                    <th style="text-align:right">CẬP NHẬT VAI TRÒ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td>
                            @php
                                $nameParts = preg_split('/\s+/', trim((string) $account->name), -1, PREG_SPLIT_NO_EMPTY);
                                $initials = collect(array_slice($nameParts, -2))->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
                            @endphp
                            <div style="display:flex;align-items:center;gap:10px">
                                @if($account->avatar)
                                    <img src="{{ asset('storage/'.$account->avatar) }}" alt="{{ $account->name }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--lime);background:#132a1e">
                                @else
                                    <div style="width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#1b3e2b;color:var(--lime);font-weight:800">{{ $initials ?: '?' }}</div>
                                @endif
                                <b>{{ $account->name }}</b>
                            </div>
                            <div class="muted" style="font-size:0.85rem">{{ $account->email }}</div>
                        </td>
                        <td>
                            <div class="mono">{{ $account->phone ?: 'Chưa có SĐT' }}</div>
                            <div class="muted" style="font-size:0.8rem">{{ $account->email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}</div>
                            @if($account->role === 'customer')
                                <a class="lime-link" style="font-size:0.8rem" href="{{ route('admin.customers.show', $account) }}">MỞ CUSTOMER 360</a>
                            @endif
                        </td>
                        <td>
                            <span class="status {{ $account->role === 'super-admin' ? 'completed' : ($account->role === 'admin' ? 'lime' : '') }}" style="{{ $account->role === 'customer' ? 'background:#132a1e;color:var(--lime)' : '' }}">
                                {{ $roleMap[$account->role] ?? ($account->role ? ucfirst($account->role) : '—') }}
                            </span>
                        </td>
                        <td class="mono">{{ $account->orders_count }}</td>
                        <td class="mono" style="color:var(--lime)">{{ number_format($account->completed_spend ?? 0, 0, ',', '.') }} ₫</td>
                        <td class="muted">{{ $account->created_at?->format('d/m/Y') }}</td>
                        <td style="text-align:right">
                            @if(auth()->user()->role === 'super-admin')
                                <form class="actions" method="POST" action="{{ route('admin.accounts.role', $account) }}" style="justify-content:flex-end">
                                    @csrf @method('PATCH')
                                    <select name="role" style="padding:5px 8px;font-size:0.82rem;background:#09150f;color:var(--text);border:1px solid var(--border-panel);border-radius:4px">
                                        <option value="customer" @selected($account->role === 'customer')>Khách hàng</option>
                                        <option value="admin" @selected($account->role === 'admin')>Quản trị viên</option>
                                        <option value="super-admin" @selected($account->role === 'super-admin')>Quản trị tối cao</option>
                                    </select>
                                    <button class="btn small" type="submit">LƯU</button>
                                </form>
                            @else
                                <span class="muted" style="font-size:0.85rem">Chỉ xem</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted" style="text-align:center;padding:24px">Chưa có tài khoản nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:18px">{{ $accounts->links() }}</div>
</section>
@endsection
