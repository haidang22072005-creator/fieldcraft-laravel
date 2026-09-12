<?php

namespace Tests\Feature;

use App\Models\CustomizationJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\TeamProfile;
use App\Models\User;
use App\Services\AdminIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminProMaxFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user, string $status = 'completed', array $extra = []): Order
    {
        return Order::create(array_merge(['number' => 'FC-'.str()->upper(str()->random(8)), 'user_id' => $user->id, 'subtotal' => 100000, 'discount' => 0, 'shipping_fee' => 0, 'total' => 100000, 'payment_method' => 'cod', 'payment_status' => $status === 'completed' ? 'unpaid' : 'pending', 'status' => $status, 'recipient_name' => 'Buyer', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email, 'province' => 'Ha Noi', 'district' => 'Nam Tu Liem', 'ward' => 'My Dinh 1', 'address_line' => '1 Test Street'], $extra));
    }

    public function test_dashboard_revenue_and_finance_exclude_cancelled_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->order($customer, 'completed', ['total' => 250000, 'created_at' => now()]);
        $this->order($customer, 'cancelled', ['total' => 900000]);
        $metrics = app(AdminIntelligenceService::class)->dashboard();
        $finance = app(AdminIntelligenceService::class)->finance();
        $this->assertSame(250000, $metrics['completed_revenue']);
        $this->assertSame(250000, $metrics['total_order_value']);
        $this->assertSame(250000, $finance['total_completed_revenue']);
        $this->assertSame(900000, $finance['cancelled_order_value']);
    }

    public function test_revenue_ranges_and_ops_radar_are_real_time_data(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $recent = $this->order($customer, 'completed', ['total' => 180000, 'created_at' => now()->subDays(2)]);
        $paidNoWaybill = $this->order($customer, 'confirmed', ['payment_status' => 'paid']);
        $stuck = $this->order($customer, 'shipping', ['updated_at' => now()->subDays(3)]);
        $delivered = $this->order($customer, 'shipping', ['shipping_status' => 'delivered']);
        Payment::create(['order_id' => $paidNoWaybill->id, 'provider' => 'momo', 'request_id' => str()->uuid(), 'amount' => 100000, 'status' => 'failed']);
        Payment::create(['order_id' => $paidNoWaybill->id, 'provider' => 'momo', 'request_id' => str()->uuid(), 'amount' => 100000, 'status' => 'failed']);
        $revenue = app(AdminIntelligenceService::class)->revenue(7);
        $types = app(AdminIntelligenceService::class)->opsRadar()->pluck('type');
        $this->assertSame(180000, $revenue['totals']['completed_revenue']);
        $this->assertContains('paid_without_waybill', $types);
        $this->assertContains('shipping_stuck', $types);
        $this->assertContains('ghn_delivered_not_completed', $types);
        $this->assertContains('repeated_payment_failures', $types);
        $this->assertNotNull($recent->id);
        $this->assertNotNull($stuck->id);
        $this->assertNotNull($delivered->id);
    }

    public function test_variant_inventory_restock_and_product_performance_use_actual_orders(): void
    {
        $product = Product::create(['name' => 'Boot', 'slug' => 'boot-'.str()->random(8), 'category' => 'Giày', 'brand' => 'Adidas']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'BOOT-40-'.str()->random(5), 'color' => 'Đỏ', 'size' => '40', 'price' => 200000, 'stock' => 2, 'stud_type' => 'FG', 'foot_shape' => 'wide', 'surface_type' => 'Cỏ tự nhiên', 'low_stock_threshold' => 3]);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer);
        OrderItem::create(['order_id' => $order->id, 'product_variant_id' => $variant->id, 'product_name' => 'Boot', 'sku' => $variant->sku, 'color' => 'Đỏ', 'size' => '40', 'unit_price' => 200000, 'quantity' => 2]);
        $service = app(AdminIntelligenceService::class);
        $matrix = $service->inventoryMatrix()->first()['colors'][0]['variants'][0];
        $radar = $service->restockRadar()->firstWhere('variant_id', $variant->id);
        $performance = $service->productPerformance()->firstWhere('product_id', $product->id);
        $this->assertSame('FG', $matrix['stud_type']);
        $this->assertTrue($matrix['low_stock']);
        $this->assertSame('restock_recommended', $radar['recommendation']);
        $this->assertSame(2, $performance['sold_quantity']);
        $this->assertSame('Đỏ', $performance['top_selling_color']);
    }

    public function test_team_profile_ownership_and_customization_transitions_are_protected(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($owner)->postJson(route('teams.store'), ['team_name' => 'Fieldcraft FC'])->assertCreated();
        $team = TeamProfile::firstOrFail();
        $this->actingAs($other)->getJson(route('teams.show', $team))->assertForbidden();
        $this->actingAs($admin)->getJson(route('admin.teams.show', $team))->assertOk();
        $order = $this->order($owner, 'pending');
        $customer360 = app(AdminIntelligenceService::class)->customer360($owner);
        $this->assertSame(1, $customer360['total_orders']);
        $this->assertCount(1, $customer360['teams']);
        $job = CustomizationJob::create(['order_id' => $order->id, 'status' => 'design_pending', 'print_name' => 'A']);
        $this->actingAs($admin)->patchJson(route('admin.customization-jobs.status', $job), ['status' => 'customer_approval'])->assertOk();
        $this->actingAs($owner)->patchJson(route('customization-jobs.status', $job), ['status' => 'approved'])->assertOk();
        $this->actingAs($owner)->patchJson(route('customization-jobs.status', $job), ['status' => 'completed'])->assertStatus(422);
    }
}
