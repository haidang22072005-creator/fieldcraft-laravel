<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\CartManager;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, CartManager $cart): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không chính xác.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $cart->mergeGuestCart($request);
        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $request->user()->role === 'super-admin'
            ? redirect()->intended(route('admin.dashboard'))
            : redirect()->intended(route('settings'));
    }

    public function registerCreate(): View { return view('auth.register'); }

    public function registerStore(Request $request, CartManager $cart): RedirectResponse
    {
        $data = $request->validate(['name'=>['required','string','max:100'],'email'=>['required','email','unique:users'],'password'=>['required','string','min:8','confirmed']]);
        $user = User::create([...$data, 'role'=>'customer']);
        Auth::login($user);
        $request->session()->regenerate();
        $cart->mergeGuestCart($request);
        $user->sendEmailVerificationNotification();
        return redirect()->route('verification.notice');
    }

    public function settings(Request $request): View { return view('settings', ['user'=>$request->user()]); }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:100'],'email'=>['required','email','unique:users,email,'.$request->user()->id],'password'=>['nullable','string','min:8','confirmed']]);
        if (blank($data['password'])) unset($data['password']);
        $request->user()->update($data);
        return back()->with('success','Đã cập nhật hồ sơ.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('store.home');
    }
}
