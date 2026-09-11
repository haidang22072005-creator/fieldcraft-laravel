<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OrderUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.ghn.token', 'test-token');
        config()->set('services.ghn.shop_id', '219637');
        config()->set('services.ghn.from_district_id', 3440);
        config()->set('services.ghn.from_ward_code', '13004');
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $attributes));
    }

    private function variant(int $stock = 10, int $price = 100000): ProductVariant
    {
        $product = Product::create(['name' => 'Order UX Boot', 'slug' => 'ux-'.uniqid(), 'category' => 'Giày', 'is_active' => true]);
        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'UX-'.uniqid(), 'color' => 'Đen', 'size' => '42', 'price' => $price, 'stock' => $stock]);
    }

    private function order(User $user, ProductVariant $variant, int $quantity = 1): Order
    {
        return app(CreateOrder::class)->handle(new Collection([
            ['product_variant_id' => $variant->id, 'quantity' => $quantity],
        ]), [
            'user_id' => $user->id, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => 'pending',
            'shipping_fee' => 35000, 'recipient_name' => 'Nguyen An', 'recipient_phone' => '0912345678',
            'recipient_email' => $user->email, 'province' => 'Ha Noi', 'district' => 'Nam Tu Liem', 'ward' => 'My Dinh 1',
            'address_line' => '1 Test Street', 'to_district_id' => 1600, 'to_ward_code' => '00001',
        ]);
    }

    public function test_owner_can_view_eager_loaded_order_detail(): void
    {
        $user = $this->user();
        $order = $this->order($user, $this->variant());

        $this->actingAs($user)->getJson(route('purchases.show', $order))
            ->assertOk()
            ->assertJsonPath('order.number', $order->number)
            ->assertJsonPath('order.recipient.name', 'Nguyen An')
            ->assertJsonPath('order.items.0.sku', $order->items->first()->sku)
            ->assertJsonPath('order.shipping_fee', 35000)
            ->assertJsonPath('order.total', 135000);
    }

    public function test_other_customer_cannot_view_order_or_tracking(): void
    {
        $owner = $this->user();
        $order = $this->order($owner, $this->variant());
        $other = $this->user();

        $this->actingAs($other)->get(route('purchases.show', $order))->assertForbidden();
        $this->actingAs($other)->getJson(route('purchases.tracking', $order))->assertForbidden();
    }

    public function test_tracking_uses_ghn_and_normalizes_status(): void
    {
        Http::fake(['*v2/shipping-order/detail' => Http::response(['code' => 200, 'data' => ['status' => 'transporting']])]);
        $owner = $this->user();
        $order = $this->order($owner, $this->variant());
        $order->update(['ghn_order_code' => 'GHN-TRACK', 'shipping_status' => 'created']);

        $this->actingAs($owner)->getJson(route('purchases.tracking', $order))
            ->assertOk()->assertJsonPath('tracking.status', 'transporting')->assertJsonPath('tracking.source', 'ghn');
    }

    public function test_tracking_falls_back_to_stored_status_when_ghn_fails(): void
    {
        Http::fake(['*v2/shipping-order/detail' => Http::response(['code' => 500], 500)]);
        $owner = $this->user();
        $order = $this->order($owner, $this->variant());
        $order->update(['ghn_order_code' => 'GHN-TRACK', 'shipping_status' => 'ready_to_pick']);

        $this->actingAs($owner)->getJson(route('purchases.tracking', $order))
            ->assertOk()->assertJsonPath('tracking.status', 'ready_to_pick')->assertJsonPath('tracking.source', 'stored');
    }

    public function test_reorder_uses_current_stock_and_skips_unavailable_items(): void
    {
        $user = $this->user();
        $available = $this->order($user, $this->variant(stock: 4), 2);
        $unavailable = $this->order($user, $this->variant(stock: 1), 1);

        $this->actingAs($user)->postJson(route('purchases.reorder', $available))
            ->assertOk()->assertJsonCount(1, 'added');
        $this->actingAs($user)->postJson(route('purchases.reorder', $unavailable))
            ->assertOk()->assertJsonCount(1, 'skipped');

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $available->items->first()->product_variant_id, 'quantity' => 2]);
    }

    public function test_customer_cancel_route_reuses_idempotent_cancel_action(): void
    {
        Http::fake(['*v2/shipping-order/cancel' => Http::response(['code' => 200, 'data' => []])]);
        $user = $this->user();
        $variant = $this->variant(stock: 3);
        $order = $this->order($user, $variant, 1);
        $order->update(['ghn_order_code' => 'GHN-CANCEL', 'shipping_status' => 'created']);

        $this->actingAs($user)->postJson(route('purchases.cancel', $order))->assertOk();
        $this->actingAs($user)->postJson(route('purchases.cancel', $order))->assertOk();

        $this->assertSame(3, $variant->fresh()->stock);
        Http::assertSentCount(1);
    }

    public function test_expedite_request_is_persisted_once(): void
    {
        $user = $this->user();
        $order = $this->order($user, $this->variant());

        $this->actingAs($user)->postJson(route('purchases.expedite', $order))
            ->assertOk()->assertJson(['requested' => true]);
        $this->actingAs($user)->postJson(route('purchases.expedite', $order))
            ->assertOk()->assertJson(['requested' => false]);
        $this->assertNotNull($order->fresh()->expedite_requested_at);
    }

    public function test_print_route_is_owner_only(): void
    {
        $owner = $this->user();
        $order = $this->order($owner, $this->variant());
        $this->actingAs($owner)->get(route('purchases.print', $order))->assertOk()->assertSee($order->number);
        $this->actingAs($this->user())->get(route('purchases.print', $order))->assertForbidden();
    }

    public function test_seed_catalog_contains_real_variant_choices(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(2, Product::where('slug', 'sample-1')->firstOrFail()->variants()->count());
        $this->assertGreaterThanOrEqual(7, Product::where('slug', 'sample-2')->firstOrFail()->variants()->count());
        $this->assertGreaterThanOrEqual(10, Product::where('slug', 'sample-3')->firstOrFail()->variants()->count());
        $this->assertSame(0, ProductVariant::query()->where('stock', '<', 0)->count());
        $this->assertSame(ProductVariant::query()->count(), ProductVariant::query()->distinct('sku')->count('sku'));
    }
}
