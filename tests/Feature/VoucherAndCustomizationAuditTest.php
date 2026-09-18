<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Coupon;
use App\Models\CustomizationJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class VoucherAndCustomizationAuditTest extends TestCase
{
    use RefreshDatabase;

    private function variant(): ProductVariant
    {
        $product = Product::query()->create([
            'name' => 'Audit boot',
            'slug' => 'audit-'.str()->random(8),
            'category' => 'Giày',
            'is_active' => true,
        ]);

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'AUDIT-'.str()->random(8),
            'color' => 'Đen',
            'size' => '42',
            'price' => 100000,
            'stock' => 5,
        ]);
    }

    private function order(User $customer): Order
    {
        return app(CreateOrder::class)->handle(
            new Collection([['product_variant_id' => $this->variant()->id, 'quantity' => 1]]),
            [
                'user_id' => $customer->id,
                'payment_method' => 'cod',
                'payment_status' => 'unpaid',
                'status' => 'completed',
                'shipping_fee' => 0,
            ],
        );
    }

    public function test_customer360_displays_coupon_used_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        Coupon::query()->create([
            'code' => 'AUDIT10',
            'type' => 'percent',
            'value' => 10,
            'usage_limit' => 10,
            'used_count' => 3,
            'user_id' => $customer->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('3 / 10', false)
            ->assertDontSee('times_used', false);
    }

    public function test_customer_can_submit_optional_customization_from_purchase_detail(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer);
        $item = $order->items()->firstOrFail();

        $this->actingAs($customer)
            ->get(route('purchases.show', $order))
            ->assertOk()
            ->assertSee('name="customization_name"', false)
            ->assertSee('không bắt buộc', false);

        $this->actingAs($customer)
            ->post(route('customization-jobs.store'), [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'customization_name' => 'HAI',
                'customization_number' => '10',
                'customization_notes' => 'Mặt lưng',
            ])
            ->assertRedirect(route('purchases.show', $order));

        $this->assertDatabaseHas('customization_jobs', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'customer_id' => $customer->id,
            'status' => 'design_pending',
            'customization_name' => 'HAI',
            'customization_number' => '10',
            'customization_notes' => 'Mặt lưng',
        ]);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'customization_name' => 'HAI',
            'customization_number' => '10',
            'customization_notes' => 'Mặt lưng',
        ]);
    }

    public function test_customer_customization_submission_is_owned_by_the_order_customer(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer);
        $item = $order->items()->firstOrFail();

        $this->actingAs($other)
            ->postJson(route('customization-jobs.store'), [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'customization_name' => 'NO',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('customization_jobs', 0);
    }

    public function test_ordinary_purchase_does_not_create_customization_data(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer);
        $item = $order->items()->firstOrFail();

        $this->assertNull($item->customization_name);
        $this->assertNull($item->customization_number);
        $this->assertNull($item->customization_notes);
        $this->assertDatabaseCount('customization_jobs', 0);
    }
}
