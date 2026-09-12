<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'role' => ['nullable', 'in:customer,admin,super-admin']]);
        $accounts = User::withCount('orders')->withSum(['orders as completed_spend' => fn ($q) => $q->where('status', 'completed')], 'total')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))->latest()->paginate(20)->withQueryString();
        return view('admin.accounts.index', compact('accounts', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireSuperAdmin($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'admin']);
        return back()->with('success', 'Đã tạo tài khoản quản trị viên.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->requireSuperAdmin($request);
        $role = $request->validate(['role' => ['required', 'in:customer,admin,super-admin']])['role'];
        if ($user->is($request->user()) && $role !== 'super-admin') throw ValidationException::withMessages(['role' => 'Không thể tự hạ quyền tài khoản đang đăng nhập.']);
        if ($user->role === 'super-admin' && $role !== 'super-admin' && User::where('role', 'super-admin')->count() <= 1) throw ValidationException::withMessages(['role' => 'Không thể hạ quyền super-admin cuối cùng.']);
        DB::transaction(function () use ($user, $role): void { User::lockForUpdate()->findOrFail($user->id)->update(['role' => $role]); });
        return back()->with('success', 'Đã cập nhật quyền tài khoản.');
    }

    private function requireSuperAdmin(Request $request): void
    { abort_unless($request->user()?->role === 'super-admin', 403); }
}
