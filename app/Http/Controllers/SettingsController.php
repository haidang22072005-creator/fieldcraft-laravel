<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings', ['user' => $request->user(), 'addresses' => $request->user()?->addresses()->latest('is_default')->get() ?? collect()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['name'=>['required','string','max:100'],'email'=>['required','email','unique:users,email,'.$user->id],'phone'=>['nullable','string','max:25'],'gender'=>['nullable','in:male,female,other'],'birthday'=>['nullable','date'],'locale'=>['required','in:vi,en'],'theme'=>['required','in:light,dark,system']]);
        if ($data['email'] !== $user->email) $data['email_verified_at'] = null;
        $user->update($data);
        return back()->with('success', 'Đã cập nhật hồ sơ. Email mới cần được xác nhận khi cấu hình mail server.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate(['avatar'=>['required','image','max:5120']]); $user=$request->user();
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->update(['avatar'=>$request->file('avatar')->store('avatars','public')]);
        return back()->with('success','Đã cập nhật ảnh đại diện.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data=$request->validate(['current_password'=>['required','current_password'],'password'=>['required','confirmed',Password::min(8)]]);
        $request->user()->update(['password'=>$data['password']]);
        return back()->with('success','Đã đổi mật khẩu.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->user()->update(['marketing_opt_in'=>$request->boolean('marketing_opt_in'),'order_updates_opt_in'=>$request->boolean('order_updates_opt_in')]);
        return back()->with('success','Đã lưu tùy chọn thông báo.');
    }

    public function logoutDevices(Request $request): RedirectResponse
    {
        $request->validate(['password'=>['required','current_password']]); Auth::logoutOtherDevices($request->input('password'));
        return back()->with('success','Đã đăng xuất khỏi các thiết bị khác.');
    }

    public function storeAddress(Request $request): RedirectResponse { $this->saveAddress($request, new Address); return back()->with('success','Đã thêm địa chỉ.'); }
    public function updateAddress(Request $request, Address $address): RedirectResponse { abort_unless($address->user_id === $request->user()->id, 403); $this->saveAddress($request, $address); return back()->with('success','Đã cập nhật địa chỉ.'); }
    public function destroyAddress(Request $request, Address $address): RedirectResponse { abort_unless($address->user_id === $request->user()->id, 403); $address->delete(); return back()->with('success','Đã xóa địa chỉ.'); }

    private function saveAddress(Request $request, Address $address): void
    {
        $data=$request->validate(['label'=>['required','string','max:30'],'recipient_name'=>['required','string','max:100'],'phone'=>['required','string','max:25'],'province_code'=>['required','string','max:100'],'ward_code'=>['nullable','string','max:100'],'address_line'=>['required','string','max:255'],'is_default'=>['nullable','boolean']]);
        if ($request->boolean('is_default')) $request->user()->addresses()->update(['is_default'=>false]);
        $address->fill($data); $address->user_id=$request->user()->id; $address->is_default=$request->boolean('is_default'); $address->save();
    }

    public function destroyAccount(Request $request): RedirectResponse
    {
        $request->validate(['password'=>['required','current_password']]); $user=$request->user(); Auth::logout(); $user->delete();
        $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('store.home')->with('success','Tài khoản đã được xóa.');
    }
}
