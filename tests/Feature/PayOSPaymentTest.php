<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PayOSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayOSPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.payos', [
            'base_url' => 'https://api-merchant.payos.vn', 'client_id' => 'client', 'api_key' => 'api',
            'checksum_key' => 'checksum', 'return_url' => 'http://localhost/payments/payos/return',
            'cancel_url' => 'http://localhost/payments/payos/cancel',
        ]);
        config()->set('services.ghn.token', 'ghn-token'); config()->set('services.ghn.shop_id', '123');
        config()->set('services.ghn.from_name', 'Fieldcraft'); config()->set('services.ghn.from_phone', '0900000000');
        config()->set('services.ghn.from_address', 'Kho'); config()->set('services.ghn.from_district_id', 3440);
        config()->set('services.ghn.from_ward_code', '13004');
    }

    private function variant(int $stock = 5): ProductVariant
    {
        $product = Product::create(['name' => 'Giày QR', 'slug' => 'qr-'.uniqid(), 'category' => 'Giày', 'is_active' => true]);
        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'QR-'.uniqid(), 'color' => 'Đen', 'size' => '42', 'price' => 100000, 'stock' => $stock]);
    }

    private function fakeGateways(): void
    {
        Http::fake(function (ClientRequest $request) {
            if (str_contains($request->url(), '/shipping-order/fee')) return Http::response(['code' => 200, 'data' => ['total' => 30000]]);
            if (str_contains($request->url(), '/shipping-order/create')) return Http::response(['code' => 200, 'data' => ['order_code' => 'GHN-PAYOS', 'fee' => 30000]]);
            if (str_contains($request->url(), '/v2/payment-requests')) {
                $this->assertTrue(is_int($request['orderCode']));
                $this->assertDatabaseHas('payments', ['provider' => 'bank_qr', 'provider_order_id' => (string) $request['orderCode'], 'amount' => $request['amount']]);
                $raw = 'amount='.$request['amount'].'&cancelUrl='.$request['cancelUrl'].'&description='.$request['description'].'&orderCode='.$request['orderCode'].'&returnUrl='.$request['returnUrl'];
                $this->assertSame(hash_hmac('sha256', $raw, 'checksum'), $request['signature']);
                return Http::response(['code' => '00', 'desc' => 'success', 'data' => [
                    'orderCode' => $request['orderCode'], 'amount' => $request['amount'], 'paymentLinkId' => 'LINK-'.$request['orderCode'],
                    'status' => 'PENDING', 'checkoutUrl' => 'https://pay.payos.vn/'.$request['orderCode'],
                ]]);
            }
            return Http::response([], 404);
        });
    }

    private function checkout(User $user, ProductVariant $variant)
    {
        $this->actingAs($user)->postJson(route('cart.add'), ['product_variant_id' => $variant->id, 'quantity' => 1])->assertOk();
        return $this->post(route('checkout.store'), [
            'recipient_name' => 'Nguyễn An', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email,
            'province' => 'Đà Nẵng', 'district' => 'Hải Châu', 'ward' => 'Hải Châu I',
            'to_district_id' => 1600, 'to_ward_code' => '00001', 'address_line' => '01 Nguyễn Văn Linh',
            'payment_method' => 'bank_qr',
        ]);
    }

    private function webhook(Payment $payment, int $amount = 130000, bool $success = true): array
    {
        $data = [
            'orderCode' => (int) $payment->provider_order_id, 'amount' => $amount, 'description' => 'FC'.substr($payment->provider_order_id, -7),
            'accountNumber' => '12345678', 'reference' => 'FT-LAB-001', 'transactionDateTime' => '2026-09-11 10:00:00',
            'currency' => 'VND', 'paymentLinkId' => 'LINK-'.$payment->provider_order_id,
            'code' => $success ? '00' : '99', 'desc' => $success ? 'Thành công' : 'Thất bại',
        ];
        return ['code' => $success ? '00' : '99', 'desc' => $data['desc'], 'success' => $success, 'data' => $data, 'signature' => app(PayOSService::class)->signWebhookData($data)];
    }

    public function test_bank_qr_creates_order_and_pending_payment_before_payos_redirect(): void
    {
        $this->fakeGateways(); $user = User::factory()->create(['role' => 'customer']);
        $response = $this->checkout($user, $this->variant());
        $order = Order::firstOrFail(); $payment = Payment::firstOrFail();

        $response->assertRedirect('https://pay.payos.vn/'.$payment->provider_order_id);
        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame('bank_qr', $order->payment_method);
        $this->assertSame('pending', $payment->status);
        $this->assertMatchesRegularExpression('/^\d+$/', $payment->provider_order_id);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/shipping-order/create'));
    }

    public function test_webhook_rejects_invalid_signature_and_amount_mismatch(): void
    {
        $this->fakeGateways(); $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant()); $payment = Payment::with('order')->firstOrFail();
        $invalid = $this->webhook($payment); $invalid['signature'] = 'invalid';

        $this->postJson(route('payos.webhook'), $invalid)->assertForbidden();
        $this->postJson(route('payos.webhook'), $this->webhook($payment, 1))->assertUnprocessable();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_valid_duplicate_webhook_marks_paid_and_creates_ghn_once(): void
    {
        $this->fakeGateways(); $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant()); $payment = Payment::with('order')->firstOrFail();
        $payload = $this->webhook($payment);

        $this->postJson(route('payos.webhook'), $payload)->assertOk();
        $this->postJson(route('payos.webhook'), $payload)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'transaction_id' => 'FT-LAB-001']);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'payment_status' => 'paid', 'status' => 'pending', 'ghn_order_code' => 'GHN-PAYOS']);
        $this->assertCount(1, Http::recorded(fn ($request) => str_contains($request->url(), '/shipping-order/create')));
    }

    public function test_failed_payment_can_retry_same_order_with_new_numeric_code_and_paid_cannot_retry(): void
    {
        $this->fakeGateways(); $user = User::factory()->create(['role' => 'customer']);
        $this->checkout($user, $this->variant(stock: 2)); $first = Payment::with('order')->firstOrFail();
        $this->postJson(route('payos.webhook'), $this->webhook($first, success: false))->assertOk();

        $response = $this->post(route('payos.retry', $first->order));
        $second = Payment::latest('id')->firstOrFail();
        $response->assertRedirect('https://pay.payos.vn/'.$second->provider_order_id);
        $this->assertDatabaseCount('orders', 1); $this->assertDatabaseCount('payments', 2);
        $this->assertSame($first->order_id, $second->order_id); $this->assertNotSame($first->provider_order_id, $second->provider_order_id);

        $this->postJson(route('payos.webhook'), $this->webhook($second))->assertOk();
        $this->post(route('payos.retry', $second->order))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 2);
    }
}
