<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use App\Services\AdminIntelligenceService;

class CustomerController extends Controller
{
    public function index(): View { return view('admin.customers.index', ['customers' => User::query()->where('role', 'customer')->withCount('orders')->latest()->paginate(20)]); }
    public function show(User $user, AdminIntelligenceService $intelligence): View|JsonResponse { abort_unless($user->role === 'customer', 404); $customer360 = $intelligence->customer360($user); if (request()->expectsJson()) return response()->json(['data' => $customer360]); return view('admin.customers.show', ['customer' => $user, 'orders' => $user->orders()->with('items')->latest()->get(), 'customer360' => $customer360]); }
    public function resetPassword(User $user): RedirectResponse { abort_unless($user->role === 'customer', 404); $user->update(['password' => Hash::make(str()->random(32))]); return back()->with('success', 'Đã cấp lại mật khẩu. Hãy yêu cầu khách hàng dùng luồng khôi phục mật khẩu.'); }
}
