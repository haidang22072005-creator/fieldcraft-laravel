<?php

use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MoMoPaymentController;
use App\Http\Controllers\PayOSPaymentController;
use App\Http\Controllers\MoMoSandboxController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('store.home');
Route::get('/products', [StorefrontController::class, 'products'])->name('store.products');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/items/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::patch('/cart/items/{variant}/increase', [CartController::class, 'increase'])->name('cart.increase');
Route::patch('/cart/items/{variant}/decrease', [CartController::class, 'decrease'])->name('cart.decrease');
Route::patch('/cart/items/{variant}/selection', [CartController::class, 'select'])->name('cart.select');
Route::patch('/cart/selection', [CartController::class, 'selectAll'])->name('cart.select-all');
Route::delete('/cart/items/{variant}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/checkout', [CheckoutController::class, 'create'])->middleware(['auth', 'verified'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware(['auth', 'verified'])->name('checkout.store');
Route::get('/payments/momo/return', [MoMoPaymentController::class, 'showReturn'])->middleware('auth')->name('momo.return');
Route::post('/payments/momo/ipn', [MoMoPaymentController::class, 'ipn'])->middleware('throttle:60,1')->name('momo.ipn');
Route::post('/orders/{order}/payments/momo/retry', [MoMoPaymentController::class, 'retry'])->middleware(['auth', 'verified', 'throttle:6,1'])->name('momo.retry');
Route::get('/orders/{order}/payments/bank', [CheckoutController::class, 'showBankPayment'])->middleware(['auth', 'verified'])->name('payments.bank');
Route::get('/payments/payos/return', [PayOSPaymentController::class, 'showReturn'])->middleware('auth')->name('payos.return');
Route::get('/payments/payos/cancel', [PayOSPaymentController::class, 'cancel'])->middleware('auth')->name('payos.cancel');
Route::post('/payments/payos/webhook', [PayOSPaymentController::class, 'webhook'])->middleware('throttle:60,1')->name('payos.webhook');
Route::get('/payments/payos/{payment}/status', [PayOSPaymentController::class, 'status'])->middleware(['auth', 'verified'])->name('payos.status');
Route::post('/orders/{order}/payments/payos/retry', [PayOSPaymentController::class, 'retry'])->middleware(['auth', 'verified', 'throttle:6,1'])->name('payos.retry');
if ((app()->environment('local') && config('services.momo.simulator_enabled') === true) || app()->environment('testing')) {
    Route::post('/orders/{order}/payments/momo/simulate-success', [MoMoPaymentController::class, 'simulateSuccess'])->middleware(['auth', 'verified', 'throttle:6,1'])->name('momo.simulate-success');
    Route::get('/orders/{order}/payments/momo/sandbox', [MoMoSandboxController::class, 'show'])->middleware(['auth', 'verified'])->name('momo.sandbox');
    Route::post('/orders/{order}/payments/momo/sandbox', [MoMoSandboxController::class, 'submit'])->middleware(['auth', 'verified', 'throttle:6,1'])->name('momo.sandbox.submit');
}
Route::middleware(['auth', 'verified'])->prefix('locations')->name('locations.')->group(function () {
    Route::get('/provinces', [LocationController::class, 'provinces'])->name('provinces');
    Route::get('/districts/{provinceId}', [LocationController::class, 'districts'])->whereNumber('provinceId')->name('districts');
    Route::get('/wards/{districtId}', [LocationController::class, 'wards'])->whereNumber('districtId')->name('wards');
    Route::post('/calculate-fee', [LocationController::class, 'calculateFee'])->name('calculate-fee');
});
Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest')->name('login.store');
Route::get('/register', [AuthController::class, 'registerCreate'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'registerStore'])->middleware('guest')->name('register.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/email/verify', fn () => view('auth.verify-email'))->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('settings')->with('success', 'Email đã được xác thực.');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) return redirect()->route('settings');
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'Đã gửi lại email xác thực.');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->middleware('auth')->name('settings.profile');
Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('auth')->name('settings.password');
Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->middleware('auth')->name('settings.avatar');
Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->middleware('auth')->name('settings.notifications');
Route::post('/settings/logout-devices', [SettingsController::class, 'logoutDevices'])->middleware('auth')->name('settings.logout-devices');
Route::delete('/settings/account', [SettingsController::class, 'destroyAccount'])->middleware('auth')->name('settings.account');
Route::post('/settings/addresses', [SettingsController::class, 'storeAddress'])->middleware('auth')->name('settings.addresses.store');
Route::put('/settings/addresses/{address}', [SettingsController::class, 'updateAddress'])->middleware('auth')->name('settings.addresses.update');
Route::delete('/settings/addresses/{address}', [SettingsController::class, 'destroyAddress'])->middleware('auth')->name('settings.addresses.destroy');
Route::get('/purchases', [CheckoutController::class, 'purchases'])->middleware(['auth', 'verified'])->name('purchases');
Route::middleware(['auth', 'verified'])->prefix('purchases')->name('purchases.')->group(function () {
    Route::get('/{order}/print', [PurchaseController::class, 'print'])->name('print');
    Route::get('/{order}/tracking', [PurchaseController::class, 'tracking'])->middleware('throttle:30,1')->name('tracking');
    Route::post('/{order}/reorder', [PurchaseController::class, 'reorder'])->name('reorder');
    Route::post('/{order}/cancel', [PurchaseController::class, 'cancel'])->name('cancel');
    Route::post('/{order}/expedite', [PurchaseController::class, 'expedite'])->middleware('throttle:6,1440')->name('expedite');
    Route::get('/{order}', [PurchaseController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [OrderController::class, 'dashboard'])->name('dashboard');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('coupons', CouponController::class)->except('show');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{user}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{user}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.reset-password');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
});
