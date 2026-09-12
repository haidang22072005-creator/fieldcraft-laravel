<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CrossSellRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\BootPassportService;
use App\Services\CustomerSegmentService;
use App\Services\CrossSellService;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class Phase3Phase4BackendTest extends TestCase
{
    use RefreshDatabase;

    private function variant(string $category = 'Giày', int $stock = 3, int $price = 100000): ProductVariant
    {
        $product = Product::create(['name' => 'Phase boot', 'slug' => uniqid('phase-'), 'brand' => 'FC', 'category' => $category, 'is_active' => true]);
        return ProductVariant::create(['product_id' => $product->id, 'sku' => uniqid('SKU-'), 'color' => 'Đen', 'size' => '42', 'price' => $price, 'stock' => $stock, 'stud_type' => 'FG']);
    }

    private function completedOrder(User $user, ProductVariant $variant, int $price = 100000): void
    {
        app(CreateOrder::class)->handle(new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]), ['user_id' => $user->id, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => 'completed', 'shipping_fee' => 0]);
    }

    public function test_loyalty_customer360_and_segments_use_completed_orders_only(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant(price: 6000000);
        $this->completedOrder($user, $variant);
        app(CreateOrder::class)->handle(new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]), ['user_id' => $user->id, 'payment_method' => 'cod', 'payment_status' => 'pending', 'status' => 'cancelled', 'shipping_fee' => 0]);

        $profile = app(LoyaltyService::class)->profile($user);
        $this->assertSame('PLAYER', $profile['tier']);
        $this->assertSame(6000000, $profile['completed_spend']);
        $this->assertContains('khách mới', app(CustomerSegmentService::class)->for($user));
        $this->assertArrayHasKey('loyalty', app(\App\Services\AdminIntelligenceService::class)->customer360($user));
    }

    public function test_cross_sell_second_hand_and_passport_are_safe_and_idempotent(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $source = $this->variant();
        $active = $this->variant(stock: 2);
        $inactive = $this->variant(stock: 2);
        $inactive->product->update(['is_active' => false]);
        $empty = $this->variant(stock: 0);
        CrossSellRule::create(['source_type' => 'product', 'source_value' => (string) $source->product_id, 'recommended_product_id' => $active->product_id, 'priority' => 10, 'is_active' => true]);
        CrossSellRule::create(['source_type' => 'product', 'source_value' => (string) $source->product_id, 'recommended_product_id' => $inactive->product_id, 'priority' => 9, 'is_active' => true]);
        CrossSellRule::create(['source_type' => 'product', 'source_value' => (string) $source->product_id, 'recommended_product_id' => $empty->product_id, 'priority' => 8, 'is_active' => true]);
        $this->assertSame([$active->product_id], app(CrossSellService::class)->recommend($source->product)->pluck('id')->all());
        $this->actingAs($user)->getJson(route('admin.cross-sell.recommend', ['product_id' => $source->product_id]))->assertForbidden();

        $this->completedOrder($user, $source);
        $this->actingAs($user)->postJson(route('second-hand.store'), ['brand' => 'FC', 'product_name' => 'Used boot', 'size' => '42', 'condition' => 'good', 'asking_price' => 500000, 'payout_method' => 'voucher'])->assertCreated();
        $this->assertDatabaseHas('second_hand_listings', ['user_id' => $user->id, 'status' => 'submitted']);
        $this->assertSame(1, count(app(BootPassportService::class)->generateForUser($user->id)));
    }

    public function test_passport_abandoned_cart_campaign_and_provider_endpoints_are_available(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = $this->variant();
        $this->completedOrder($user, $variant);
        $this->actingAs($user)->getJson(route('boot-passports.index'))->assertOk();
        $this->assertDatabaseCount('boot_passports', 0);
        $this->assertCount(1, app(BootPassportService::class)->generateForUser($user->id));
        $this->assertCount(1, app(BootPassportService::class)->generateForUser($user->id));

        $cart = Cart::create(['user_id' => $user->id, 'last_activity_at' => now()->subDays(2)]);
        CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->actingAs($admin)->getJson(route('admin.abandoned-carts.index'))->assertOk()->assertJsonPath('data.0.id', $cart->id);
        $this->actingAs($admin)->postJson(route('admin.abandoned-carts.contacted', $cart))->assertOk();

        $response = $this->actingAs($admin)->postJson(route('admin.campaigns.store'), ['name' => 'Matchday', 'product_ids' => [$variant->product_id], 'starts_at' => now()->addDay()->toISOString(), 'ends_at' => now()->addDays(2)->toISOString()])->assertCreated();
        $campaignId = $response->json('data.id');
        $this->actingAs($admin)->postJson(route('admin.campaigns.approve', $campaignId))->assertOk();
        $this->actingAs($admin)->patchJson(route('admin.campaigns.status', $campaignId), ['status' => 'scheduled'])->assertOk();
        $this->actingAs($admin)->getJson(route('admin.shipping-hub.providers'))->assertOk()->assertJsonPath('data.1.connected', false);
        $this->actingAs($admin)->getJson(route('admin.global-search', ['q' => 'Phase']))->assertOk();
    }
}
