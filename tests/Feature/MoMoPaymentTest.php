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
            'request_type' => 'payWithCC',
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

    private function fakeGateways(bool $momoSuccess = true, string $requestType = 'payWithCC'): void
    {
        Http::fake(function (ClientRequest $request) use ($momoSuccess, $requestType) {
            if (str_contains($request->url(), '/shipping-order/fee')) return Http::response(['code' => 200, 'data' => ['total' => 30000]]);
            if (str_contains($request->url(), '/shipping-order/create')) {
                $this->assertDatabaseCount('orders', 1);
                return Http::response(['code' => 200, 'data' => ['order_code' => 'GHN-MOMO', 'fee' => 30000]]);
            }
            if (str_contains($request->url(), 'test-payment.momo.vn')) {
                $attempt = Payment::where('request_id', $request['requestId'])->firstOrFail();
                $this->assertSame($attempt->provider_order_id, $request['orderId']);
                $this->assertDatabaseHas('orders', ['id' => $attempt->order_id, 'payment_method' => 'momo']);
                $this->assertDatabaseHas('payments', ['request_id' => $request['requestId'], 'provider_order_id' => $request['orderId'], 'amount' => (int) $request['amount']]);
                $this->assertSame($requestType, $request['requestType']);
                return $momoSuccess
                    ? Http::response(['resultCode' => 0, 'message' => 'Success', 'orderId' => $request['orderId'], 'payUrl' => 'https://momo.test/pay/'.$request['requestId']])
                    : Http::response(['resultCode' => 42, 'message' => 'Failed'], 400);
            }
            return Http::response([], 404);
        });
    }

    public function test_pay_with_atm_uses_exact_create_signature_and_unique_payment_identity(): void
    {
        config()->set('services.momo.request_type', 'payWithATM');
        $this->fakeGateways(requestType: 'payWithATM');
        $user = User::factory()->create(['role' => 'customer']);

        $this->checkout($user, $this->variant(), 'momo')->assertRedirect();

        $payment = Payment::where('provider', 'momo')->firstOrFail();
        Http::assertSent(function (ClientRequest $request) use ($payment): bool {
            if (! str_contains($request->url(), 'test-payment.momo.vn')) return false;

            $raw = implode('&', [
                'accessKey=TESTACCESS',
                'amount='.$request['amount'],
                'extraData='.$request['extraData'],
                'ipnUrl='.$request['ipnUrl'],
                'orderId='.$request['orderId'],
                'orderInfo='.$request['orderInfo'],
                'partnerCode='.$request['partnerCode'],
                'redirectUrl='.$request['redirectUrl'],
                'requestId='.$request['requestId'],
                'requestType='.$request['requestType'],
            ]);

            return $request['requestType'] === 'payWithATM'
                && $request['requestId'] === $payment->request_id
                && $request['orderId'] === $payment->provider_order_id
                && hash_equals(hash_hmac('sha256', $raw, 'TESTSECRET'), $request['signature']);
        });
    }

    private function enableSimulator(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('services.momo.simulator_enabled', true);
        $this->withSession(['_token' => 'lab-csrf-token'])->withHeader('X-CSRF-TOKEN', 'lab-csrf-token');
    }

    private function ipn(Payment $payment, int $resultCode = 0, ?int $amount = null, ?string $gatewayOrderId = null): array
    {
        $data = [
            'partnerCode' => 'TESTPARTNER', 'orderId' => $gatewayOrderId ?? $payment->provider_order_id, 'requestId' => $payment->request_id,
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
        $response = $this->checkout($user, $this->variant(price: 100000), 'momo', ['amount' => 1]);

        $order = Order::firstOrFail();
        $payment = Payment::firstOrFail();
        $response->assertRedirect('https://momo.test/pay/'.$payment->request_id);
        $this->assertMatchesRegularExpression('/^ORD\d{16}-P\d+$/', $payment->provider_order_id);
        $this->assertSame(130000, (int) $order->total);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider_order_id' => $order->number.'-P'.$payment->id, 'amount' => 130000, 'status' => 'pending', 'pay_url' => 'https://momo.test/pay/'.$payment->request_id]);
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

    public function test_late_success_after_local_cancellation_is_recorded_for_refund_without_ghn(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant(stock: 2);
        $this->checkout($user, $variant, 'momo');
        $payment = Payment::with('order')->firstOrFail();

        $this->postJson(route('momo.ipn'), $this->ipn($payment, 99))->assertOk();
        $beforeLateCallback = $variant->fresh()->stock;
        $this->postJson(route('momo.ipn'), $this->ipn($payment, 0))->assertOk();
        $this->postJson(route('momo.ipn'), $this->ipn($payment, 0))->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id, 'status' => 'paid', 'refund_status' => 'required',
            'refund_reason' => 'Thanh toán được ghi nhận sau khi đơn đã hủy.',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'status' => 'cancelled', 'payment_status' => 'paid']);
        $this->assertSame($beforeLateCallback, $variant->fresh()->stock);
        $this->assertCount(0, Http::recorded(fn ($request) => str_contains($request->url(), '/shipping-order/create')));
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
        $this->postJson(route('momo.ipn'), $this->ipn($payment, gatewayOrderId: 'ORD-WRONG-P999'))->assertNotFound();
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
        $retryResponse = $this->post(route('momo.retry', $payment->order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('payments', ['order_id' => $payment->order_id, 'provider' => 'momo', 'status' => 'pending']);
        $newPayment = Payment::latest('id')->firstOrFail();
        $retryResponse->assertRedirect('https://momo.test/pay/'.$newPayment->request_id);
        $this->assertNotSame($oldRequestId, $newPayment->request_id);
        $this->assertNotSame($payment->provider_order_id, $newPayment->provider_order_id);
        $this->assertSame($payment->order_id, $newPayment->order_id);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_momo_initialization_failure_keeps_trace_and_restores_order_effects(): void
    {
        $this->fakeGateways(false);
        $user = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant(stock: 2);

        $this->checkout($user, $variant, 'momo')->assertRedirect(route('checkout'))->assertSessionHasErrors('payment_method');

        $this->assertDatabaseHas('payments', ['status' => 'failed', 'result_code' => 42, 'message' => 'Failed']);
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

    public function test_valid_signed_failed_return_updates_pending_payment_to_failed(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();
        $orderId = $payment->order_id;

        $this->get(route('momo.return', $this->ipn($payment, 99)))->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed', 'result_code' => 99]);
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'cancelled', 'payment_status' => 'failed']);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_valid_signed_success_return_updates_pending_payment_to_paid(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();

        $this->get(route('momo.return', $this->ipn($payment)))->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'transaction_id' => '123456']);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'payment_status' => 'paid', 'status' => 'pending']);
    }

    public function test_invalid_return_signature_does_not_change_pending_payment(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();
        $payload = $this->ipn($payment); $payload['signature'] = 'invalid';

        $this->get(route('momo.return', $payload))->assertOk();
        $this->get(route('momo.return', $this->ipn($payment, gatewayOrderId: 'ORD-WRONG-P999')))->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending', 'transaction_id' => null]);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'payment_status' => 'pending', 'status' => 'pending_payment']);
    }

    public function test_ipn_after_processed_return_does_not_duplicate_ghn_or_payment_effects(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();
        $payload = $this->ipn($payment);

        $this->get(route('momo.return', $payload))->assertOk();
        $this->postJson(route('momo.ipn'), $payload)->assertOk();

        $this->assertDatabaseCount('payments', 1);
        $this->assertCount(1, Http::recorded(fn ($request) => str_contains($request->url(), '/shipping-order/create')));
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_simulator_is_unavailable_when_disabled(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('services.momo.simulator_enabled', false);
        $this->withSession(['_token' => 'lab-csrf-token'])->withHeader('X-CSRF-TOKEN', 'lab-csrf-token');
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $order = Order::firstOrFail();

        $this->actingAs($user)->post(route('momo.simulate-success', $order))->assertNotFound();
    }

    public function test_simulator_is_unavailable_outside_local(): void
    {
        config()->set('services.momo.simulator_enabled', true);
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $order = Order::firstOrFail();

        $this->actingAs($user)->post(route('momo.simulate-success', $order))->assertNotFound();
    }

    public function test_another_user_cannot_simulate_order(): void
    {
        $this->enableSimulator();
        $this->fakeGateways();
        $owner = User::factory()->create(['role' => 'customer']);
        $this->checkout($owner, $this->variant(), 'momo');
        $order = Order::firstOrFail();
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($other)->post(route('momo.simulate-success', $order))->assertForbidden();
        $this->assertSame('pending', Payment::firstOrFail()->status);
    }

    public function test_local_simulator_uses_server_payment_identity_and_marks_same_order_paid(): void
    {
        $this->enableSimulator();
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();

        $this->post(route('momo.simulate-success', $payment->order), [
            'amount' => 1, 'requestId' => 'fake', 'orderId' => 'fake',
        ])->assertRedirect(route('purchases'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'order_id' => $payment->order_id, 'status' => 'paid', 'result_code' => 0]);
        $this->assertStringStartsWith('LAB-', (string) $payment->fresh()->transaction_id);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'payment_status' => 'paid', 'status' => 'pending', 'ghn_order_code' => 'GHN-MOMO']);
    }

    public function test_duplicate_simulation_is_idempotent_and_does_not_duplicate_ghn(): void
    {
        $this->enableSimulator();
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();

        $this->post(route('momo.simulate-success', $payment->order))->assertRedirect(route('purchases'));
        $transactionId = $payment->fresh()->transaction_id;
        $this->post(route('momo.simulate-success', $payment->order))->assertSessionHasErrors('payment');

        $this->assertSame($transactionId, $payment->fresh()->transaction_id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertCount(1, Http::recorded(fn ($request) => str_contains($request->url(), '/shipping-order/create')));
    }

    public function test_paid_order_cannot_be_simulated_again(): void
    {
        $this->enableSimulator();
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(), 'momo');
        $payment = Payment::with('order')->firstOrFail();
        $this->postJson(route('momo.ipn'), $this->ipn($payment))->assertOk();

        $this->post(route('momo.simulate-success', $payment->order))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 1);
    }
}
