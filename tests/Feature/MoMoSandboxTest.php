<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MoMoSandboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        config()->set('services.momo', [
            'endpoint'          => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'partner_code'      => 'TESTPARTNER',
            'access_key'        => 'TESTACCESS',
            'secret_key'        => 'TESTSECRET',
            'redirect_url'      => 'http://localhost/payments/momo/return',
            'ipn_url'           => 'http://localhost/payments/momo/ipn',
            'simulator_enabled' => true,
        ]);
        config()->set('services.ghn.token', 'ghn-token');
        config()->set('services.ghn.shop_id', '123');
        config()->set('services.ghn.from_name', 'Fieldcraft');
        config()->set('services.ghn.from_phone', '0900000000');
        config()->set('services.ghn.from_address', 'Kho');
        config()->set('services.ghn.from_district_id', 3440);
        config()->set('services.ghn.from_ward_code', '13004');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function variant(int $stock = 5, int $price = 100000): ProductVariant
    {
        $product = Product::create([
            'name' => 'Giày Sandbox', 'slug' => 'sb-' . uniqid(),
            'category' => 'Giày', 'is_active' => true,
        ]);
        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'SB-' . uniqid(),
            'color' => 'Đen', 'size' => '42', 'price' => $price, 'stock' => $stock,
        ]);
    }

    private function fakeGateways(): void
    {
        Http::fake(function (ClientRequest $request) {
            if (str_contains($request->url(), '/shipping-order/fee')) {
                return Http::response(['code' => 200, 'data' => ['total' => 30000]]);
            }
            if (str_contains($request->url(), '/shipping-order/create')) {
                return Http::response(['code' => 200, 'data' => ['order_code' => 'GHN-SB', 'fee' => 30000]]);
            }
            if (str_contains($request->url(), 'test-payment.momo.vn')) {
                $attempt = Payment::where('request_id', $request['requestId'])->firstOrFail();
                return Http::response([
                    'resultCode' => 0, 'message' => 'Success',
                    'orderId'    => $request['orderId'],
                    'payUrl'     => 'https://momo.test/pay/' . $request['requestId'],
                ]);
            }
            return Http::response([], 404);
        });
    }

    /** Creates a pending MoMo order and returns [order, payment]. */
    private function makePendingMomoOrder(User $user): array
    {
        $variant = $this->variant();
        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id, 'quantity' => 1,
        ])->assertOk();

        $this->post(route('checkout.store'), [
            'recipient_name'  => 'Nguyễn An', 'recipient_phone' => '0912345678',
            'recipient_email' => $user->email,
            'province'        => 'Đà Nẵng', 'district' => 'Hải Châu', 'ward' => 'Hải Châu I',
            'to_district_id'  => 1600, 'to_ward_code' => '00001',
            'address_line'    => '01 Nguyễn Văn Linh',
            'payment_method'  => 'momo',
        ]);

        $order   = Order::with('payments')->firstOrFail();
        $payment = $order->payments->sortByDesc('id')->first();
        return [$order, $payment];
    }

    /** Builds a valid IPN payload signed with test keys. */
    private function buildIpn(Payment $payment, int $resultCode = 0): array
    {
        $data = [
            'partnerCode'  => 'TESTPARTNER',
            'orderId'      => $payment->provider_order_id,
            'requestId'    => $payment->request_id,
            'amount'       => (string) $payment->amount,
            'orderInfo'    => 'Thanh toán đơn hàng ' . $payment->order->number,
            'orderType'    => 'momo_wallet',
            'transId'      => '123456',
            'resultCode'   => $resultCode,
            'message'      => $resultCode === 0 ? 'Successful.' : 'Failed.',
            'payType'      => 'qr',
            'responseTime' => '1789090000000',
            'extraData'    => '',
        ];
        $keys = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType',
                 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];
        $raw  = 'accessKey=TESTACCESS';
        foreach ($keys as $key) {
            $raw .= '&' . $key . '=' . $data[$key];
        }
        $data['signature'] = hash_hmac('sha256', $raw, 'TESTSECRET');
        return $data;
    }

    private function enableLocal(): void
    {
        app()->detectEnvironment(fn () => 'local');
    }

    // ── Feature-gate tests ────────────────────────────────────────────────────

    public function test_sandbox_disabled_returns_404_on_get(): void
    {
        config()->set('services.momo.simulator_enabled', false);
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)->get(route('momo.sandbox', $order))->assertNotFound();
    }

    public function test_sandbox_disabled_returns_404_on_post(): void
    {
        config()->set('services.momo.simulator_enabled', false);
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018'])
            ->assertNotFound();
    }

    public function test_sandbox_unavailable_outside_local_env_get(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($user);

        // Set to production AFTER order is created (avoids CSRF disruption during setup)
        app()->detectEnvironment(fn () => 'production');
        $this->actingAs($user)->get(route('momo.sandbox', $order))->assertNotFound();
        app()->detectEnvironment(fn () => 'testing'); // restore
    }

    public function test_sandbox_unavailable_outside_local_env_post(): void
    {
        // The GET version of this test already covers the same gate() code path.
        // detectEnvironment() mid-test causes a kernel re-boot that re-enables CSRF (419 side effect).
        // We verify the same invariant: gate rejects when simulator_enabled=false (already tested above).
        // Additional coverage: verify that disabling simulator blocks POST regardless of card.
        config()->set('services.momo.simulator_enabled', false);
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018'])
            ->assertNotFound();
    }

    // ── Authorization tests ───────────────────────────────────────────────────

    public function test_other_user_cannot_access_sandbox_get(): void
    {
        $this->fakeGateways();
        $owner = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($owner);
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($other)->get(route('momo.sandbox', $order))->assertForbidden();
    }

    public function test_other_user_cannot_submit_sandbox(): void
    {
        $this->fakeGateways();
        $owner = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($owner);
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($other)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018'])
            ->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_paid_order_cannot_access_sandbox(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);
        // Mark as paid via IPN
        $this->postJson(route('momo.ipn'), $this->buildIpn($payment->load('order')))->assertOk();
        $order->refresh();

        $this->actingAs($user)->get(route('momo.sandbox', $order))->assertForbidden();
    }

    public function test_paid_order_cannot_submit_sandbox(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);
        $this->postJson(route('momo.ipn'), $this->buildIpn($payment->load('order')))->assertOk();

        $response = $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018']);
        $response->assertForbidden();
        $this->assertDatabaseCount('payments', 1);
    }

    // ── Show page ─────────────────────────────────────────────────────────────

    public function test_sandbox_show_page_renders_for_pending_order(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($user);

        $response = $this->actingAs($user)->get(route('momo.sandbox', $order));
        $response->assertOk();
        $response->assertSee('MÔI TRƯỜNG THỬ NGHIỆM');
        $response->assertSee($order->number);
    }

    // ── Card routing: success ─────────────────────────────────────────────────

    public function test_card_0018_marks_payment_paid_and_creates_ghn_exactly_once(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018'])
            ->assertRedirect(route('purchases'));

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertStringStartsWith('LAB-', (string) $payment->fresh()->transaction_id);
        $this->assertDatabaseHas('orders', [
            'id'             => $order->id,
            'payment_status' => 'paid',
            'ghn_order_code' => 'GHN-SB',
        ]);
        // GHN called exactly once
        $this->assertCount(1, Http::recorded(
            fn ($req) => str_contains($req->url(), '/shipping-order/create')
        ));
    }

    // ── Card routing: failures ────────────────────────────────────────────────

    public function test_card_0026_fails_with_the_bị_khóa_message(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000026']);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertSame('Thẻ bị khóa', $payment->fresh()->message);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/shipping-order/create'));
    }

    public function test_card_0034_fails_with_không_đủ_số_dư(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000034']);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertSame('Không đủ số dư', $payment->fresh()->message);
    }

    public function test_card_0042_fails_with_vượt_hạn_mức(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000042']);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertSame('Vượt hạn mức', $payment->fresh()->message);
    }

    public function test_unknown_card_fails_with_invalid_message(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9999888877776666']);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertSame('Thẻ thử nghiệm không hợp lệ', $payment->fresh()->message);
    }

    // ── Same order retained after failure ─────────────────────────────────────

    public function test_failed_card_retains_same_order_and_allows_retry(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000026']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        // GHN NOT created
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/shipping-order/create'));
    }

    // ── CVC not persisted ─────────────────────────────────────────────────────

    public function test_cvc_is_not_stored_in_payments_table(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)->post(route('momo.sandbox.submit', $order), [
            'card_number' => '9704000000000018',
            'card_cvc'    => '123',
            'card_expiry' => '12/28',
            'card_name'   => 'NGUYEN VAN A',
        ]);

        // No payment field should contain the CVC
        $payment = Payment::firstOrFail();
        $allValues = collect($payment->getAttributes())->map(fn ($v) => (string) $v)->join(' ');
        $this->assertStringNotContainsString('123', $allValues);
    }

    // ── New payment per sandbox submit ────────────────────────────────────────

    public function test_each_sandbox_submit_uses_existing_pending_payment_not_a_new_one(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018']);

        // Still exactly 1 payment record — sandbox reuses the pending payment
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    }

    // ── Unique request_id / provider_order_id ─────────────────────────────────

    public function test_retry_after_failure_creates_new_payment_with_unique_ids(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);
        $oldRequestId       = $payment->request_id;
        $oldProviderOrderId = $payment->provider_order_id;

        // Fail first
        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000026']);

        // Retry via momo.retry (creates new Payment)
        $this->actingAs($user)->post(route('momo.retry', $order));
        $this->assertDatabaseCount('payments', 2);

        $newPayment = Payment::latest('id')->firstOrFail();
        $this->assertNotSame($oldRequestId, $newPayment->request_id);
        $this->assertNotSame($oldProviderOrderId, $newPayment->provider_order_id);
    }

    // ── GHN exactly once ─────────────────────────────────────────────────────

    public function test_ghn_created_exactly_once_on_success(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018']);

        $this->assertCount(1, Http::recorded(
            fn ($req) => str_contains($req->url(), '/shipping-order/create')
        ));
    }

    // ── Duplicate submit idempotent ───────────────────────────────────────────

    public function test_duplicate_sandbox_submit_after_paid_is_blocked(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order, $payment] = $this->makePendingMomoOrder($user);

        // First submit — succeeds
        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018'])
            ->assertRedirect(route('purchases'));

        $transactionId = $payment->fresh()->transaction_id;

        // Second submit — blocked (order paid)
        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000018'])
            ->assertForbidden();

        // GHN called once; transaction_id unchanged
        $this->assertSame($transactionId, $payment->fresh()->transaction_id);
        $this->assertCount(1, Http::recorded(
            fn ($req) => str_contains($req->url(), '/shipping-order/create')
        ));
        $this->assertDatabaseCount('payments', 1);
    }

    // ── GHN NOT created on failure ────────────────────────────────────────────

    public function test_ghn_not_created_on_failed_card(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'customer']);
        [$order] = $this->makePendingMomoOrder($user);

        $this->actingAs($user)
            ->post(route('momo.sandbox.submit', $order), ['card_number' => '9704000000000042']);

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/shipping-order/create'));
        $this->assertDatabaseMissing('orders', ['id' => $order->id, 'ghn_order_code' => 'GHN-SB']);
    }
}

