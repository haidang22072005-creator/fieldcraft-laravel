<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\IntelligenceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\TeamOrderDraftController;
use App\Http\Controllers\Admin\AbandonedCartController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\BootPassportController as AdminBootPassportController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\ChatMessageController as AdminChatMessageController;
use App\Http\Controllers\Admin\CrossSellController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\KanbanController;
use App\Http\Controllers\Admin\LoyaltyController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SecondHandController as AdminSecondHandController;
use App\Http\Controllers\Admin\ShippingHubController;
use App\Http\Controllers\Admin\TrendController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerVoucherController;
use App\Http\Controllers\CustomizationJobController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\NotificationController as CustomerNotificationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MoMoPaymentController;
use App\Http\Controllers\MoMoSandboxController;
use App\Http\Controllers\PayOSPaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SecondHandController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\TeamProfileController;
use App\Http\Controllers\BootPassportController;
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
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->route('settings');
    }
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'Đã gửi lại email xác thực.');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->middleware('auth')->name('settings.profile');
Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('auth')->name('settings.password');
Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->middleware('auth')->name('settings.avatar');
Route::delete('/settings/avatar', [SettingsController::class, 'removeAvatar'])->middleware('auth')->name('settings.avatar.remove');
Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->middleware('auth')->name('settings.notifications');
Route::post('/settings/logout-devices', [SettingsController::class, 'logoutDevices'])->middleware('auth')->name('settings.logout-devices');
Route::delete('/settings/account', [SettingsController::class, 'destroyAccount'])->middleware('auth')->name('settings.account');
Route::post('/settings/addresses', [SettingsController::class, 'storeAddress'])->middleware('auth')->name('settings.addresses.store');
Route::put('/settings/addresses/{address}', [SettingsController::class, 'updateAddress'])->middleware('auth')->name('settings.addresses.update');
Route::delete('/settings/addresses/{address}', [SettingsController::class, 'destroyAddress'])->middleware('auth')->name('settings.addresses.destroy');
Route::get('/purchases', [CheckoutController::class, 'purchases'])->middleware(['auth', 'verified'])->name('purchases');
Route::get('/vouchers', [CustomerVoucherController::class, 'index'])->middleware(['auth', 'verified'])->name('vouchers.index');
Route::middleware(['auth', 'verified'])->prefix('support')->name('support.')->group(function () {
    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{supportTicket}', [SupportTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{supportTicket}/messages', [SupportTicketController::class, 'reply'])->name('tickets.reply');
});
Route::middleware(['auth', 'verified'])->prefix('chat')->name('chat.')->group(function () {
    Route::get('/messages', [ChatMessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [ChatMessageController::class, 'store'])->name('messages.store');
});
Route::middleware(['auth', 'verified'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [CustomerNotificationController::class, 'index'])->name('index');
    Route::patch('/{notification}/read', [CustomerNotificationController::class, 'read'])->name('read');
});
Route::middleware(['auth', 'verified'])->prefix('purchases')->name('purchases.')->group(function () {
    Route::get('/{order}/print', [PurchaseController::class, 'print'])->name('print');
    Route::get('/{order}/tracking', [PurchaseController::class, 'tracking'])->middleware('throttle:30,1')->name('tracking');
    Route::post('/{order}/reorder', [PurchaseController::class, 'reorder'])->name('reorder');
    Route::post('/{order}/cancel', [PurchaseController::class, 'cancel'])->name('cancel');
    Route::post('/{order}/expedite', [PurchaseController::class, 'expedite'])->middleware('throttle:6,1440')->name('expedite');
    Route::post('/{order}/items/{orderItem}/review', [ReviewController::class, 'store'])->middleware('throttle:10,1')->name('review.store');
    Route::get('/{order}', [PurchaseController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [OrderController::class, 'dashboard'])->name('dashboard');
    Route::get('/chat', [AdminChatMessageController::class, 'page'])->name('chat');
    Route::get('/chat/customers', [AdminChatMessageController::class, 'customers'])->name('chat.customers.index');
    Route::get('/chat/customers/{user}/messages', [AdminChatMessageController::class, 'show'])->name('chat.customers.messages');
    Route::post('/chat/customers/{user}/messages', [AdminChatMessageController::class, 'reply'])->name('chat.customers.messages.store');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('coupons', CouponController::class)->except('show');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{user}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{user}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.reset-password');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/kanban', KanbanController::class)->name('orders.kanban');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/refund/processing', [RefundController::class, 'processing'])->name('orders.refund.processing');
    Route::post('/orders/{order}/refund/confirm', [RefundController::class, 'confirm'])->name('orders.refund.confirm');
    Route::post('/orders/{order}/refund/failed', [RefundController::class, 'failed'])->name('orders.refund.failed');
    Route::post('/orders/{order}/sync-ghn', [OrderController::class, 'syncGhn'])->middleware('throttle:30,1')->name('orders.sync-ghn');
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/status', [AdminReviewController::class, 'status'])->name('reviews.status');
    Route::post('/reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts/staff', [AccountController::class, 'store'])->middleware('role:super-admin')->name('accounts.store');
    Route::patch('/accounts/{user}/role', [AccountController::class, 'updateRole'])->middleware('role:super-admin')->name('accounts.role');
    Route::prefix('intelligence')->name('intelligence.')->group(function () {
        Route::get('/dashboard', [IntelligenceController::class, 'dashboard'])->name('dashboard');
        Route::get('/revenue', [IntelligenceController::class, 'revenue'])->name('revenue');
        Route::get('/ops-radar', [IntelligenceController::class, 'opsRadar'])->name('ops-radar');
        Route::get('/finance', [IntelligenceController::class, 'finance'])->name('finance');
        Route::get('/inventory', [IntelligenceController::class, 'inventory'])->name('inventory');
        Route::get('/restock', [IntelligenceController::class, 'restock'])->name('restock');
        Route::get('/performance', [IntelligenceController::class, 'performance'])->name('performance');
        Route::get('/customers/{user}/360', [IntelligenceController::class, 'customer360'])->name('customers.360');
    });
    Route::apiResource('teams', TeamProfileController::class)->only(['index', 'show', 'store', 'update'])->parameters(['teams' => 'teamProfile']);
    Route::post('/teams/{teamProfile}/members', [TeamProfileController::class, 'storeMember'])->name('teams.members.store');
    Route::patch('/teams/{teamProfile}/members/{teamMember}', [TeamProfileController::class, 'updateMember'])->name('teams.members.update');
    Route::get('/teams/{teamProfile}/draft-reorder', [TeamOrderDraftController::class, 'showOrCreate'])->name('teams.draft-reorder');
    Route::post('/teams/{teamProfile}/draft-reorder', [TeamOrderDraftController::class, 'store'])->name('teams.draft-reorder.store');
    Route::get('/team-order-drafts/{teamOrderDraft}', [TeamOrderDraftController::class, 'show'])->name('team-order-drafts.show');
    Route::patch('/team-order-drafts/{teamOrderDraft}', [TeamOrderDraftController::class, 'update'])->name('team-order-drafts.update');
    Route::post('/customization-jobs', [CustomizationJobController::class, 'store'])->name('customization-jobs.store');
    Route::get('/customization-jobs/{customizationJob}', [CustomizationJobController::class, 'show'])->name('customization-jobs.show');
    Route::patch('/customization-jobs/{customizationJob}/status', [CustomizationJobController::class, 'updateStatus'])->name('customization-jobs.status');
    Route::prefix('loyalty')->name('loyalty.')->group(function () {
        Route::get('/', [LoyaltyController::class, 'index'])->name('index');
        Route::get('/segments', [LoyaltyController::class, 'segments'])->name('segments');
        Route::get('/customers/{user}', [LoyaltyController::class, 'show'])->name('show');
        Route::post('/customers/{user}/vouchers', [LoyaltyController::class, 'issueVoucher'])->name('vouchers.store');
    });
    Route::prefix('support/tickets')->name('support.tickets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('index');
        Route::get('/{supportTicket}', [\App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->name('show');
        Route::post('/{supportTicket}/messages', [\App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->name('reply');
        Route::patch('/{supportTicket}/status', [\App\Http\Controllers\Admin\SupportTicketController::class, 'status'])->name('status');
    });
    Route::prefix('cross-sell')->name('cross-sell.')->group(function () {
        Route::get('/', [CrossSellController::class, 'index'])->name('index');
        Route::post('/', [CrossSellController::class, 'store'])->name('store');
        Route::get('/recommendations', [CrossSellController::class, 'recommend'])->name('recommend');
        Route::delete('/{crossSellRule}', [CrossSellController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('abandoned-carts')->name('abandoned-carts.')->group(function () {
        Route::get('/', [AbandonedCartController::class, 'index'])->name('index');
        Route::post('/{cart}/contacted', [AbandonedCartController::class, 'contacted'])->name('contacted');
    });
    Route::prefix('trends')->name('trends.')->group(function () {
        Route::get('/', [TrendController::class, 'index'])->name('index');
        Route::post('/', [TrendController::class, 'store'])->name('store');
        Route::post('/provider', [TrendController::class, 'provider'])->name('provider');
    });
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
        Route::post('/{campaign}/approve', [CampaignController::class, 'approve'])->name('approve');
        Route::patch('/{campaign}/status', [CampaignController::class, 'status'])->name('status');
    });
    Route::prefix('second-hand')->name('second-hand.')->group(function () {
        Route::get('/', [AdminSecondHandController::class, 'index'])->name('index');
        Route::get('/{secondHandListing}', [AdminSecondHandController::class, 'show'])->name('show');
        Route::patch('/{secondHandListing}/status', [AdminSecondHandController::class, 'status'])->name('status');
    });
    Route::prefix('boot-passports')->name('boot-passports.')->group(function () {
        Route::get('/', [AdminBootPassportController::class, 'index'])->name('index');
        Route::post('/generate', [AdminBootPassportController::class, 'generate'])->name('generate');
        Route::get('/{bootPassport}', [AdminBootPassportController::class, 'show'])->name('show');
    });
    Route::prefix('shipping-hub')->name('shipping-hub.')->group(function () {
        Route::get('/', [ShippingHubController::class, 'index'])->name('index');
        Route::get('/providers', [ShippingHubController::class, 'providers'])->name('providers');
    });
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/sync-low-stock', [NotificationController::class, 'syncLowStock'])->name('sync-low-stock');
        Route::patch('/{notification}/read', [NotificationController::class, 'read'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
    });
    Route::get('/global-search', GlobalSearchController::class)->name('global-search');
    if (app()->environment(['local', 'testing'])) {
        Route::post('/orders/{order}/manual-complete', [OrderController::class, 'manualComplete'])->name('orders.manual-complete');
    }
});

Route::middleware(['auth', 'verified'])->prefix('teams')->name('teams.')->group(function () {
    Route::get('/', [TeamProfileController::class, 'index'])->name('index');
    Route::get('/{teamProfile}', [TeamProfileController::class, 'show'])->name('show');
    Route::post('/', [TeamProfileController::class, 'store'])->name('store');
    Route::patch('/{teamProfile}', [TeamProfileController::class, 'update'])->name('update');
    Route::post('/{teamProfile}/members', [TeamProfileController::class, 'storeMember'])->name('members.store');
    Route::patch('/{teamProfile}/members/{teamMember}', [TeamProfileController::class, 'updateMember'])->name('members.update');
});
Route::middleware(['auth', 'verified'])->prefix('customization-jobs')->name('customization-jobs.')->group(function () {
    Route::post('/', [CustomizationJobController::class, 'store'])->name('store');
    Route::get('/{customizationJob}', [CustomizationJobController::class, 'show'])->name('show');
    Route::patch('/{customizationJob}/status', [CustomizationJobController::class, 'updateStatus'])->name('status');
});
Route::middleware(['auth', 'verified'])->prefix('second-hand')->name('second-hand.')->group(function () {
    Route::get('/', [SecondHandController::class, 'index'])->name('index');
    Route::post('/', [SecondHandController::class, 'store'])->name('store');
    Route::get('/{secondHandListing}', [SecondHandController::class, 'show'])->name('show');
});
Route::middleware(['auth', 'verified'])->prefix('boot-passports')->name('boot-passports.')->group(function () {
    Route::get('/', [BootPassportController::class, 'index'])->name('index');
    Route::get('/{bootPassport}', [BootPassportController::class, 'show'])->name('show');
});
