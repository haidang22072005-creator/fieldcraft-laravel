<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\GHNOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GHNShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.ghn.token', 'test-token');
        config()->set('services.ghn.shop_id', '219637');
        config()->set('services.ghn.from_name', 'Fieldcraft Test Sender');
        config()->set('services.ghn.from_phone', '0900000000');
        config()->set('services.ghn.from_address', '1 Test Street');
        config()->set('services.ghn.from_province_name', 'Hà Nội');
        config()->set('services.ghn.from_district_name', 'Quận Nam Từ Liêm');
        config()->set('services.ghn.from_ward_name', 'Phường Mỹ Đình 1');
        config()->set('services.ghn.from_district_id', 3440);
        config()->set('services.ghn.from_ward_code', '13004');
        config()->set('services.ghn.default_weight', 200);
    }

    private function user(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function variant(int $stock = 5, int $price = 100000): ProductVariant
    {
        $product = Product::create([
            'name' => 'GHN Boot', 'slug' => 'ghn-'.uniqid(), 'category' => 'Giày', 'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'GHN-'.uniqid(), 'color' => 'Đen', 'size' => '42',
            'price' => $price, 'stock' => $stock,
        ]);
    }

    private function addToCart(User $user, ProductVariant $variant, int $quantity = 1): void
    {
        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id, 'quantity' => $quantity,
        ])->assertOk();
    }

    private function order(User $user, ProductVariant $variant, int $quantity = 1): Order
    {
        return app(CreateOrder::class)->handle(new Collection([
            [
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ],
        ]), [
            'user_id' => $user->id,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'shipping_fee' => 0,
        ]);
    }

    public function test_location_endpoints_proxy_ghn(): void
    {
        Http::fake([
            '*master-data/province' => Http::response(['code' => 200, 'data' => [['ProvinceID' => 1]]]),
            '*master-data/district*' => Http::response(['code' => 200, 'data' => [['DistrictID' => 2]]]),
            '*master-data/ward*' => Http::response(['code' => 200, 'data' => [['WardCode' => '3']]]),
        ]);
        $user = $this->user();

        $this->actingAs($user)->getJson('/locations/provinces')->assertOk()->assertJsonPath('data.0.ProvinceID', 1);
        $this->actingAs($user)->getJson('/locations/districts/1')->assertOk()->assertJsonPath('data.0.DistrictID', 2);
        $this->actingAs($user)->getJson('/locations/wards/2')->assertOk()->assertJsonPath('data.0.WardCode', '3');
        Http::assertSentCount(3);
    }

    public function test_fee_uses_selected_cart_weight_and_ignores_client_values(): void
    {
        Http::fake(['*v2/shipping-order/fee' => Http::response(['code' => 200, 'data' => ['total' => 48000]])]);
        $user = $this->user();
        $selected = $this->variant();
        $unselected = $this->variant();
        $this->addToCart($user, $selected, 2);
        $this->addToCart($user, $unselected, 4);
        $this->actingAs($user)->patchJson(route('cart.select', $unselected), ['selected' => false])->assertOk();

        $response = $this->actingAs($user)->postJson('/locations/calculate-fee', [
            'to_district_id' => 1600, 'to_ward_code' => '00001',
            'shipping_fee' => 1, 'subtotal' => 1, 'weight' => 1,
        ])->assertOk()->assertJson(['shipping_fee' => 48000, 'weight' => 400]);

        $response->assertJsonMissing(['shipping_fee' => 1]);
        Http::assertSent(function ($request) {
            return $request->url() === config('services.ghn.base_url').'/v2/shipping-order/fee'
                && $request['weight'] === 400
                && $request['from_district_id'] === 3440
                && $request['from_ward_code'] === '13004'
                && $request['service_type_id'] === 2
                && $request['to_district_id'] === 1600
                && ! array_key_exists('subtotal', $request->data());
        });
    }

    public function test_fee_failure_is_safe(): void
    {
        Http::fake(['*v2/shipping-order/fee' => Http::response(['code' => 500, 'message' => 'token-secret'], 500)]);
        $user = $this->user();
        $this->addToCart($user, $this->variant());

        $this->actingAs($user)->postJson('/locations/calculate-fee', [
            'to_district_id' => 1600, 'to_ward_code' => '00001',
        ])->assertStatus(502)->assertJson(['message' => 'Không thể kết nối dịch vụ giao hàng.'])
            ->assertJsonMissing(['message' => 'token-secret']);
    }

    public function test_checkout_stores_successful_waybill_and_server_fee(): void
    {
        Http::fake([
            '*v2/shipping-order/fee' => Http::response(['code' => 200, 'data' => ['total' => 35000]]),
            '*v2/shipping-order/create' => Http::response(['code' => 200, 'data' => ['order_code' => 'GHN123', 'fee' => 35000]]),
        ]);
        $user = $this->user();
        $variant = $this->variant(price: 100000);
        $this->addToCart($user, $variant);

        $this->actingAs($user)->post(route('checkout.store'), [
            'recipient_name' => 'Nguyen An', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email,
            'province' => 'Da Nang', 'district' => 'Hai Chau', 'ward' => 'Hai Chau I',
            'to_district_id' => 1600, 'to_ward_code' => '00001', 'address_line' => '01 Nguyen Van Linh',
            'payment_method' => 'cod', 'shipping_fee' => 1,
        ])->assertRedirect(route('store.home'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id, 'subtotal' => 100000, 'shipping_fee' => 35000, 'total' => 135000,
            'ghn_order_code' => 'GHN123', 'ghn_total_fee' => 35000, 'shipping_status' => 'created',
        ]);
        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/v2/shipping-order/create')
                && $request['from_name'] === 'Fieldcraft Test Sender'
                && $request['from_phone'] === '0900000000'
                && $request['from_address'] === '1 Test Street'
                && $request['from_province_name'] === 'Hà Nội'
                && $request['from_district_name'] === 'Quận Nam Từ Liêm'
                && $request['from_ward_name'] === 'Phường Mỹ Đình 1'
                && $request['from_district_id'] === 3440
                && $request['payment_type_id'] === 1
                && $request['cod_amount'] === 135000;
        });
    }

    public function test_ghn_prepaid_order_has_no_cod_amount(): void
    {
        $order = app(CreateOrder::class)->handle(new Collection([
            ['product_variant_id' => $this->variant(price: 100000)->id, 'quantity' => 1],
        ]), [
            'user_id' => $this->user()->id, 'payment_method' => 'momo', 'status' => 'pending',
            'payment_status' => 'paid', 'shipping_fee' => 35000,
            'to_district_id' => 1600, 'to_ward_code' => '00001',
        ]);

        $payload = app(GHNOrderService::class)->buildPayload($order);
        $this->assertSame(1, $payload['payment_type_id']);
        $this->assertSame(0, $payload['cod_amount']);
        $this->assertSame(135000, $order->total);
    }

    public function test_failed_waybill_leaves_local_order_valid(): void
    {
        Http::fake([
            '*v2/shipping-order/fee' => Http::response(['code' => 200, 'data' => ['total' => 25000]]),
            '*v2/shipping-order/create' => Http::response(['code' => 500, 'message' => 'internal details'], 500),
        ]);
        $user = $this->user();
        $variant = $this->variant(stock: 3);
        $this->addToCart($user, $variant, 2);

        $this->actingAs($user)->post(route('checkout.store'), [
            'recipient_name' => 'Nguyen An', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email,
            'province' => 'Da Nang', 'district' => 'Hai Chau', 'ward' => 'Hai Chau I',
            'to_district_id' => 1600, 'to_ward_code' => '00001', 'address_line' => '01 Nguyen Van Linh',
            'payment_method' => 'cod',
        ])->assertRedirect(route('store.home'));

        $this->assertDatabaseHas('orders', ['shipping_fee' => 25000, 'status' => 'pending', 'ghn_order_code' => null]);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cancellation_calls_ghn_and_restores_stock_once(): void
    {
        Http::fake([
            '*v2/shipping-order/detail' => Http::response(['code' => 200, 'data' => ['status' => 'ready_to_pick']]),
            '*v2/shipping-order/cancel' => Http::response(['code' => 200, 'data' => []]),
        ]);
        $user = $this->user();
        $variant = $this->variant(stock: 3);
        $order = app(CreateOrder::class)->handle(new Collection([
            ['product_variant_id' => $variant->id, 'quantity' => 2],
        ]), [
            'user_id' => $user->id, 'payment_method' => 'cod', 'status' => 'pending', 'payment_status' => 'pending',
            'shipping_fee' => 0,
        ]);
        $order->update(['ghn_order_code' => 'GHN123', 'shipping_status' => 'created']);
        $admin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertRedirect();

        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'shipping_status' => 'cancelled']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/shipping-order/cancel')
            && $request['order_codes'] === ['GHN123']);
        $requests = Http::recorded();
        $this->assertStringContainsString('/v2/shipping-order/detail', $requests[0][0]->url());
        $this->assertStringContainsString('/v2/shipping-order/cancel', $requests[1][0]->url());
    }

    public function test_non_cancellable_ghn_order_keeps_local_order_and_stock_unchanged(): void
    {
        Http::fake([
            '*v2/shipping-order/detail' => Http::response(['code' => 200, 'data' => ['status' => 'transporting']]),
            '*v2/shipping-order/cancel' => Http::response(['code' => 200, 'data' => []]),
        ]);
        $user = $this->user();
        $variant = $this->variant(stock: 3);
        $order = $this->order($user, $variant, 2);
        $order->update(['ghn_order_code' => 'GHN-TRANSPORTING', 'shipping_status' => 'created']);

        $this->actingAs($user)->postJson(route('purchases.cancel', $order))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending', 'shipping_status' => 'created']);
        $this->assertSame(1, $variant->fresh()->stock);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v2/shipping-order/cancel'));
    }

    public function test_ghn_cancel_failure_keeps_local_order_and_stock_unchanged(): void
    {
        Http::fake([
            '*v2/shipping-order/detail' => Http::response(['code' => 200, 'data' => ['status' => 'ready_to_pick']]),
            '*v2/shipping-order/cancel' => Http::response(['code' => 500, 'message' => 'rejected'], 500),
        ]);
        $user = $this->user();
        $variant = $this->variant(stock: 3);
        $order = $this->order($user, $variant, 2);
        $order->update(['ghn_order_code' => 'GHN-REJECTED', 'shipping_status' => 'created']);

        $this->actingAs($user)->postJson(route('purchases.cancel', $order))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending', 'shipping_status' => 'created']);
        $this->assertSame(1, $variant->fresh()->stock);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/shipping-order/detail'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/shipping-order/cancel'));
    }
}
