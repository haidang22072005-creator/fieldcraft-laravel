<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MoMoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.momo', [
            'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'partner_code' => 'TESTPARTNER', 'access_key' => 'TESTACCESS', 'secret_key' => 'TESTSECRET',
            'redirect_url' => 'http://localhost/payments/momo/return', 'ipn_url' => 'http://localhost/payments/momo/ipn',
        ]);
        config()->set('services.ghn.token', 'ghn-token');
        config()->set('services.ghn.shop_id', '123');
        config()->set('services.ghn.from_name', 'Fieldcraft');
        config()->set('services.ghn.from_phone', '0900000000');
        config()->set('services.ghn.from_address', 'Kho');
        config()->set('services.ghn.from_district_id', 3440);
        config()->set('services.ghn.from_ward_code', '13004');
    }

    private function variant(int $stock = 5, int $price = 100000): ProductVariant
    {
        $product = Product::create(['name' => 'Giày MoMo', 'slug' => 'momo-'.uniqid(), 'category' => 'Giày', 'is_active' => true]);
        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'MM-'.uniqid(), 'color' => 'Đen', 'size' => '42', 'price' => $price, 'stock' => $stock]);
    }

    private function checkout(User $user, ProductVariant $variant, string $method, array $overrides = [])
    {
        $this->actingAs($user)->postJson(route('cart.add'), ['product_variant_id' => $variant->id, 'quantity' => 1])->assertOk();
        return $this->post(route('checkout.store'), array_merge([
            'recipient_name' => 'Nguyễn An', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email,
            'province' => 'Đà Nẵng', 'district' => 'Hải Châu', 'ward' => 'Hải Châu I',
            'to_district_id' => 1600, 'to_ward_code' => '00001', 'address_line' => '01 Nguyễn Văn Linh',
            'payment_method' => $method,
        ], $overrides));
    }

    private function fakeGateways(bool $momoSuccess = true): void
    {
        Http::fake(function (ClientRequest $request) use ($momoSuccess) {
            if (str_contains($request->url(), '/shipping-order/fee')) return Http::response(['code' => 200, 'data' => ['total' => 30000]]);
            if (str_contains($request->url(), '/shipping-order/create')) {
                $this->assertDatabaseCount('orders', 1);
                return Http::response(['code' => 200, 'data' => ['order_code' => 'GHN-MOMO', 'fee' => 30000]]);
            }
            if (str_contains($request->url(), 'test-payment.momo.vn')) {
                $this->assertDatabaseHas('orders', ['number' => $request['orderId'], 'payment_method' => 'momo']);
                $this->assertDatabaseHas('payments', ['request_id' => $request['requestId'], 'amount' => (int) $request['amount']]);
                $this->assertSame('payWithCC', $request['requestType']);
                return $momoSuccess
                    ? Http::response(['resultCode' => 0, 'message' => 'Success', 'orderId' => $request['orderId'], 'payUrl' => 'https://momo.test/pay'])
                    : Http::response(['resultCode' => 42, 'message' => 'Failed'], 400);
            }
            return Http::response([], 404);
        });
    }

    private function ipn(Payment $payment, int $resultCode = 0, ?int $amount = null): array
    {
        $data = [
            'partnerCode' => 'TESTPARTNER', 'orderId' => $payment->order->number, 'requestId' => $payment->request_id,
            'amount' => (string) ($amount ?? $payment->amount), 'orderInfo' => 'Thanh toán đơn hàng '.$payment->order->number,
            'orderType' => 'momo_wallet', 'transId' => '123456', 'resultCode' => $resultCode,
            'message' => $resultCode === 0 ? 'Successful.' : 'Failed.', 'payType' => 'qr', 'responseTime' => '1789090000000', 'extraData' => '',
        ];
        $keys = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];
        $raw = 'accessKey=TESTACCESS';
        foreach ($keys as $key) $raw .= '&'.$key.'='.$data[$key];
        $data['signature'] = hash_hmac('sha256', $raw, 'TESTSECRET');
        return $data;
    }

    public function test_cod_creates_local_order_then_ghn_without_calling_momo(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'cod')->assertRedirect(route('store.home'));

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => 'pending', 'ghn_order_code' => 'GHN-MOMO']);
        $this->assertDatabaseHas('payments', ['order_id' => Order::firstOrFail()->id, 'provider' => 'cod', 'status' => 'unpaid']);
        $this->assertDatabaseCount('payments', 1);
        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), 'test-payment.momo.vn'));
    }

    public function test_momo_order_and_payment_exist_before_api_and_client_amount_is_ignored(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(price: 100000), 'momo', ['amount' => 1])->assertRedirect('https://momo.test/pay');

        $order = Order::firstOrFail();
        $this->assertSame(130000, (int) $order->total);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => 130000, 'status' => 'pending', 'pay_url' => 'https://momo.test/pay']);
        $this->assertSame('pending_payment', $order->status);
        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), '/shipping-order/create'));
    }

    public function test_valid_success_ipn_is_idempotent_and_creates_ghn_after_payment(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();

        $this->postJson(route('momo.ipn'), $this->ipn($payment))->assertOk();
        $this->postJson(route('momo.ipn'), $this->ipn($payment))->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'transaction_id' => '123456']);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'payment_status' => 'paid', 'status' => 'pending', 'ghn_order_code' => 'GHN-MOMO']);
        $this->assertCount(1, Http::recorded(fn ($request) => str_contains($request->url(), '/shipping-order/create')));
    }

    public function test_invalid_signature_and_amount_mismatch_are_rejected(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();
        $invalid = $this->ipn($payment); $invalid['signature'] = 'invalid';

        $this->postJson(route('momo.ipn'), $invalid)->assertForbidden();
        $this->postJson(route('momo.ipn'), $this->ipn($payment, amount: 1))->assertUnprocessable();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_failed_ipn_restores_stock_and_coupon_exactly_once(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant(stock: 2);
        $coupon = Coupon::create(['code' => 'MOMO10', 'type' => 'percent', 'value' => 10, 'minimum_order_value' => 0, 'usage_limit' => 5, 'per_user_limit' => 1, 'used_count' => 0, 'is_active' => true]);
        $this->checkout($user, $variant, 'momo', ['coupon' => 'MOMO10']);
        $payment = Payment::with('order')->firstOrFail();

        $payload = $this->ipn($payment, 99);
        $this->postJson(route('momo.ipn'), $payload)->assertOk();
        $this->postJson(route('momo.ipn'), $payload)->assertOk();

        $this->assertSame(2, $variant->fresh()->stock);
        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertDatabaseMissing('coupon_usages', ['order_id' => $payment->order_id]);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'status' => 'cancelled', 'payment_status' => 'failed']);

        $oldRequestId = $payment->request_id;
        $this->post(route('momo.retry', $payment->order))->assertRedirect('https://momo.test/pay');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('payments', ['order_id' => $payment->order_id, 'provider' => 'momo', 'status' => 'pending']);
        $this->assertNotSame($oldRequestId, Payment::latest('id')->value('request_id'));
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_momo_initialization_failure_keeps_trace_and_restores_order_effects(): void
    {
        $this->fakeGateways(false);
        $user = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant(stock: 2);

        $this->checkout($user, $variant, 'momo')->assertRedirect(route('checkout'))->assertSessionHasErrors('payment_method');

        $this->assertDatabaseHas('payments', ['status' => 'failed']);
        $this->assertDatabaseHas('orders', ['status' => 'cancelled', 'payment_status' => 'failed']);
        $this->assertSame(2, $variant->fresh()->stock);
    }

    public function test_paid_order_cannot_pay_again(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();
        $this->postJson(route('momo.ipn'), $this->ipn($payment))->assertOk();

        $this->post(route('momo.retry', $payment->order))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_pay_again_rejects_insufficient_stock_without_new_transaction(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant(stock: 1);
        $this->checkout($user, $variant, 'momo');
        $payment = Payment::with('order')->firstOrFail();
        $this->postJson(route('momo.ipn'), $this->ipn($payment, 99))->assertOk();
        $variant->update(['stock' => 0]);

        $this->post(route('momo.retry', $payment->order))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(0, $variant->fresh()->stock);
    }
}
