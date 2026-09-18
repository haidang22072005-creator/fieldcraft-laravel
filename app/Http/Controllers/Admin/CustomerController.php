<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Services\AdminIntelligenceService;

class CustomerController extends Controller
{
    public function index(): View { return view('admin.customers.index', ['customers' => User::query()->where('role', 'customer')->withCount('orders')->latest()->paginate(20)]); }
    public function show(User $user, AdminIntelligenceService $intelligence): View|JsonResponse
    {
        abort_unless($user->role === 'customer', 404);
        $customer360 = $intelligence->customer360($user);

        if (request()->expectsJson()) {
            return response()->json(['data' => $customer360]);
        }

        $user->load('addresses');

        return view('admin.customers.show', [
            'customer' => $user,
            'orders' => $customer360['recent_orders'],
            'reviews' => $customer360['recent_reviews'],
            'customer360' => $customer360,
        ]);
    }
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 404);
        $status = Password::broker()->sendResetLink(['email' => $user->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => 'Không thể gửi email đặt lại mật khẩu lúc này.']);
        }

        app(\App\Services\ActivityLogService::class)->record('password_reset_email_sent', $user, [], $request->user()->id);

        return back()->with('success', 'Đã gửi email đặt lại mật khẩu cho khách hàng.');
    }
}
