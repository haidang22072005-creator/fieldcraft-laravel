<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View { return view('admin.customers.index', ['customers' => User::query()->where('role', 'customer')->withCount('orders')->latest()->paginate(20)]); }
    public function show(User $user): View { abort_unless($user->role === 'customer', 404); return view('admin.customers.show', ['customer' => $user, 'orders' => $user->orders()->with('items')->latest()->get()]); }
    public function resetPassword(User $user): RedirectResponse { abort_unless($user->role === 'customer', 404); $user->update(['password' => 'Customer@123']); return back()->with('success', 'Đã cấp lại mật khẩu: Customer@123'); }
}
