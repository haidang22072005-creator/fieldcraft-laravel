<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View { return view('admin.coupons.index', ['coupons' => Coupon::latest()->paginate(15)]); }
    public function create(): View { return view('admin.coupons.form', ['coupon' => new Coupon]); }
    public function edit(Coupon $coupon): View { return view('admin.coupons.form', compact('coupon')); }
    public function store(Request $request): RedirectResponse { Coupon::create($this->data($request)); return redirect()->route('admin.coupons.index')->with('success', 'Đã tạo mã giảm giá.'); }
    public function update(Request $request, Coupon $coupon): RedirectResponse { $coupon->update($this->data($request)); return redirect()->route('admin.coupons.index')->with('success', 'Đã cập nhật mã giảm giá.'); }
    public function destroy(Coupon $coupon): RedirectResponse { $coupon->delete(); return back()->with('success', 'Đã xóa mã giảm giá.'); }
    private function data(Request $request): array { $data=$request->validate(['code'=>['required','string','max:30'],'type'=>['required','in:percent,fixed'],'value'=>['required','integer','min:1'],'minimum_order_value'=>['nullable','integer','min:0'],'usage_limit'=>['nullable','integer','min:1'],'expires_at'=>['nullable','date']]);$data['code']=strtoupper($data['code']);$data['is_active']=$request->boolean('is_active');return $data; }
}
