<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\BootPassport;
use App\Models\CrossSellRule;
use App\Models\MatchdayCampaign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SecondHandListing;
use App\Models\User;
use App\Services\AdminNotificationService;
use App\Services\BootPassportService;
use App\Services\CrossSellService;
use App\Services\FootballApiAdapter;
use App\Services\LoyaltyService;
use App\Services\MatchdayCampaignService;
use App\Services\RefundWorkflowService;
use App\Support\OrderStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BackendHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_policy_and_status_source_of_truth_are_enforced(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order($owner, OrderStatus::PENDING);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $order));
        $this->assertFalse(Gate::forUser($other)->allows('view', $order));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $order));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::PENDING, OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::PENDING, OrderStatus::SHIPPING));
        $this->assertSame([OrderStatus::CONFIRMED], OrderStatus::transitionsFrom(OrderStatus::PENDING));
    }

    public function test_loyalty_earn_and_confirmed_refund_clawback_are_idempotent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer, OrderStatus::COMPLETED, ['total' => 100000]);
        $payment = Payment::create(['order_id' => $order->id, 'provider' => 'momo', 'request_id' => str()->uuid(), 'amount' => $order->total, 'status' => 'paid', 'refund_status' => 'required']);
        $loyalty = app(LoyaltyService::class);

        $loyalty->recordCompletedOrder($order);
        $loyalty->recordCompletedOrder($order);
        $this->assertDatabaseCount('loyalty_point_transactions', 1);
        $this->assertSame(10, (int) $loyalty->profile($customer)['loyalty_points']);

        $payment->update(['refund_status' => 'refunded', 'refunded_at' => now(), 'refund_reference' => 'REF-001', 'refund_confirmed_by' => $admin->id]);
        $payment->update(['refunded_at' => now()]);

        $this->assertDatabaseCount('loyalty_point_transactions', 2);
        $this->assertDatabaseHas('loyalty_point_transactions', ['order_id' => $order->id, 'type' => 'clawback', 'points' => -10]);
        $profile = $loyalty->profile($customer);
        $this->assertSame(0, $profile['loyalty_points']);
        $this->assertSame(0, $profile['completed_spend']);
        $this->assertSame(1, AdminNotification::where('type', 'payment_refunded')->count());
    }

    public function test_boot_passport_generation_is_idempotent_and_event_driven(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create(['name' => 'Boot', 'slug' => 'boot-'.str()->random(8), 'category' => 'Giày']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'BOOT-'.str()->random(8), 'color' => 'Đen', 'size' => '42', 'price' => 100000, 'stock' => 1]);
        $order = $this->order($customer, OrderStatus::COMPLETED);
        $item = OrderItem::create(['order_id' => $order->id, 'product_variant_id' => $variant->id, 'product_name' => $product->name, 'sku' => $variant->sku, 'color' => 'Đen', 'size' => '42', 'unit_price' => 100000, 'quantity' => 1]);
        $service = app(BootPassportService::class);

        $service->generateForOrder($order);
        $service->generateForOrder($order);

        $this->assertDatabaseCount('boot_passports', 1);
        $this->assertDatabaseHas('boot_passports', ['order_item_id' => $item->id]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_second_hand_isolation_and_cross_sell_filters_are_safe(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $source = Product::create(['name' => 'Source', 'slug' => 'source-'.str()->random(8), 'category' => 'Giày', 'brand' => 'Fieldcraft']);
        $active = Product::create(['name' => 'Active', 'slug' => 'active-'.str()->random(8), 'category' => 'Giày', 'brand' => 'Fieldcraft']);
        $inactive = Product::create(['name' => 'Inactive', 'slug' => 'inactive-'.str()->random(8), 'category' => 'Giày', 'brand' => 'Fieldcraft', 'is_active' => false]);
        $empty = Product::create(['name' => 'Empty', 'slug' => 'empty-'.str()->random(8), 'category' => 'Giày', 'brand' => 'Fieldcraft']);
        $this->variant($source, 2);
        $this->variant($active, 2);
        $this->variant($inactive, 2);
        $this->variant($empty, 0);
        CrossSellRule::create(['source_type' => 'product', 'source_value' => (string) $source->id, 'recommended_product_id' => $active->id, 'priority' => 20, 'is_active' => true]);
        CrossSellRule::create(['source_type' => 'product', 'source_value' => (string) $source->id, 'recommended_product_id' => $inactive->id, 'priority' => 10, 'is_active' => true]);
        CrossSellRule::create(['source_type' => 'product', 'source_value' => (string) $source->id, 'recommended_product_id' => $empty->id, 'priority' => 5, 'is_active' => true]);
        $listing = SecondHandListing::create(['user_id' => $owner->id, 'brand' => 'Fieldcraft', 'product_name' => 'Old Boot', 'size' => '42', 'condition' => 'Good', 'asking_price' => 50000, 'payout_method' => 'cash', 'status' => 'submitted']);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $listing));
        $this->assertFalse(Gate::forUser($other)->allows('view', $listing));
        $recommendations = app(CrossSellService::class)->recommend($source);
        $this->assertSame([$active->id], $recommendations->pluck('id')->all());
        $this->assertTrue($recommendations->first()->variants->every(fn (ProductVariant $variant) => $variant->stock > 0));
    }

    public function test_notification_callback_is_idempotent_and_campaign_activation_is_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order($admin, OrderStatus::PENDING);
        $notifications = app(AdminNotificationService::class);
        $notifications->notifyOnce('payment_success', 'Payment success', $order->number, [], $order);
        $notifications->notifyOnce('payment_success', 'Payment success', $order->number, [], $order);
        $this->assertSame(1, AdminNotification::where('type', 'payment_success')->count());

        $campaign = MatchdayCampaign::create(['name' => 'Expired campaign', 'status' => 'draft', 'approved_at' => now(), 'starts_at' => now()->subDay(), 'ends_at' => now()->subMinute()]);
        app(MatchdayCampaignService::class)->updateStatus($campaign, 'scheduled', $admin->id);
        $this->expectException(ValidationException::class);
        app(MatchdayCampaignService::class)->updateStatus($campaign->fresh(), 'active', $admin->id);
    }

    public function test_refund_workflow_requires_admin_confirmation_and_is_idempotent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer, OrderStatus::COMPLETED, ['payment_method' => 'momo', 'payment_status' => 'paid']);
        $payment = Payment::create(['order_id' => $order->id, 'provider' => 'momo', 'request_id' => str()->uuid(), 'amount' => $order->total, 'status' => 'paid', 'refund_status' => 'required']);
        app(LoyaltyService::class)->recordCompletedOrder($order);

        $this->actingAs($admin)->postJson(route('admin.orders.refund.processing', $order))->assertOk()->assertJsonPath('data.refund_status', 'pending');
        $this->actingAs($admin)->postJson(route('admin.orders.refund.confirm', $order), [])->assertUnprocessable();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'refund_status' => 'pending']);

        $first = $this->actingAs($admin)->postJson(route('admin.orders.refund.confirm', $order), ['refund_reference' => 'REF-001'])->assertOk();
        $first->assertJsonPath('data.refund_status', 'refunded');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'refund_status' => 'refunded', 'refund_reference' => 'REF-001', 'refund_confirmed_by' => $admin->id]);
        $this->assertNotNull(Payment::find($payment->id)->refunded_at);
        $this->assertDatabaseCount('loyalty_point_transactions', 2);
        $this->assertDatabaseHas('loyalty_point_transactions', ['order_id' => $order->id, 'type' => 'clawback', 'points' => -10]);

        $this->actingAs($admin)->postJson(route('admin.orders.refund.confirm', $order), ['refund_reference' => 'REF-001'])->assertOk();
        $this->assertDatabaseCount('loyalty_point_transactions', 2);
        $this->assertSame(1, Payment::where('refund_status', 'refunded')->count());
    }

    public function test_refund_invalid_state_and_non_admin_are_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer, OrderStatus::PENDING, ['payment_method' => 'momo', 'payment_status' => 'paid']);
        Payment::create(['order_id' => $order->id, 'provider' => 'momo', 'request_id' => str()->uuid(), 'amount' => $order->total, 'status' => 'paid', 'refund_status' => 'none']);

        $this->actingAs($customer)->postJson(route('admin.orders.refund.processing', $order))->assertForbidden();
        $this->actingAs($other)->postJson(route('admin.orders.refund.confirm', $order), ['refund_reference' => 'REF-002'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->postJson(route('admin.orders.refund.processing', $order))->assertUnprocessable();
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'refund_status' => 'none']);
    }

    public function test_refund_failure_is_allowed_from_required_or_pending_but_not_after_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer, OrderStatus::PENDING, ['payment_method' => 'payos', 'payment_status' => 'paid']);
        $payment = Payment::create(['order_id' => $order->id, 'provider' => 'bank_qr', 'request_id' => str()->uuid(), 'amount' => $order->total, 'status' => 'paid', 'refund_status' => 'required']);

        $this->actingAs($admin)->postJson(route('admin.orders.refund.failed', $order), ['refund_reason' => 'Provider rejected refund'])->assertOk()->assertJsonPath('data.refund_status', 'failed');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'refund_status' => 'failed', 'refund_reason' => 'Provider rejected refund']);
        $this->actingAs($admin)->postJson(route('admin.orders.refund.confirm', $order), ['refund_reference' => 'REF-003'])->assertUnprocessable();
    }

    public function test_football_provider_never_claims_connected_when_fetch_is_unavailable(): void
    {
        config(['services.football.provider' => 'example-football', 'services.football.endpoint' => 'https://provider.invalid', 'services.football.api_key' => 'configured-but-no-adapter']);
        $provider = app(FootballApiAdapter::class);
        $result = $provider->fetch();

        $this->assertTrue($provider->configured());
        $this->assertFalse($provider->connected());
        $this->assertFalse($result['available']);
        $this->assertFalse($result['connected']);
        $this->assertTrue($result['configured']);
        $this->assertSame([], $result['data']);
    }

    private function order(User $user, string $status, array $extra = []): Order
    {
        return Order::create(array_merge(['number' => 'HARD-'.str()->upper(str()->random(8)), 'user_id' => $user->id, 'subtotal' => 100000, 'discount' => 0, 'shipping_fee' => 0, 'total' => 100000, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => $status, 'recipient_name' => 'Buyer', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email, 'province' => 'Ha Noi', 'district' => 'Nam Tu Liem', 'ward' => 'My Dinh', 'address_line' => '1 Test Street'], $extra));
    }

    private function variant(Product $product, int $stock): ProductVariant
    {
        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.str()->random(8), 'color' => 'Đen', 'size' => '42', 'price' => 100000, 'stock' => $stock]);
    }
}
