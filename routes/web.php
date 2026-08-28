<?php

use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('store.home');
Route::get('/products', [StorefrontController::class, 'products'])->name('store.products');
Route::post('/cart/sync', [CartController::class, 'sync'])->name('cart.sync');
Route::get('/checkout', [CheckoutController::class, 'create'])->middleware(['auth', 'verified'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware(['auth', 'verified'])->name('checkout.store');
Route::view('/admin-preview', 'admin')->name('admin.preview');
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

Route::middleware(['auth', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [OrderController::class, 'dashboard'])->name('dashboard');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('coupons', CouponController::class)->except('show');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{user}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{user}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.reset-password');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
});
