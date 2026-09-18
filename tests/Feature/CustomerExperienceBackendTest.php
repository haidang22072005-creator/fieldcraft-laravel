<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerExperienceBackendTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 5): ProductVariant
    {
        $product = Product::query()->create(['name' => 'CRM boot', 'slug' => 'crm-'.str()->random(8), 'brand' => 'Fieldcraft', 'category' => 'Giày', 'is_active' => true]);

        return ProductVariant::query()->create(['product_id' => $product->id, 'sku' => 'CRM-'.str()->random(8), 'color' => 'Đen', 'size' => '42', 'price' => 100000, 'stock' => $stock, 'stud_type' => 'FG', 'surface_type' => 'grass']);
    }

    public function test_personal_vouchers_are_owned_and_global_coupons_still_work(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = $this->variant();
        $voucher = Coupon::query()->create(['code' => 'OWNED10', 'type' => 'percent', 'value' => 10, 'user_id' => $owner->id, 'usage_limit' => 1, 'per_user_limit' => 1, 'is_active' => true]);

        $this->actingAs($admin)->postJson(route('admin.loyalty.vouchers.store', $owner), ['type' => 'fixed', 'value' => 10000])->assertCreated()->assertJsonPath('data.user_id', $owner->id);
        $this->actingAs($owner)->getJson(route('vouchers.index'))->assertOk()->assertJsonFragment(['code' => $voucher->code]);
        $this->actingAs($other)->getJson(route('vouchers.index'))->assertOk()->assertJsonMissing(['code' => $voucher->code]);

        $this->expectException(ValidationException::class);
        app(CreateOrder::class)->handle(new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]), ['user_id' => $other->id, 'payment_method' => 'cod', 'coupon_code' => 'OWNED10']);
    }

    public function test_global_coupon_and_max_discount_are_applied_server_side(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant();
        Coupon::query()->create(['code' => 'GLOBAL10', 'type' => 'percent', 'value' => 50, 'max_discount' => 10000, 'usage_limit' => 1, 'per_user_limit' => 1, 'is_active' => true]);

        $order = app(CreateOrder::class)->handle(new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]), ['user_id' => $customer->id, 'payment_method' => 'cod', 'coupon_code' => 'global10']);

        $this->assertSame(10000, (int) $order->discount);
    }

    public function test_avatar_is_private_to_owner_safe_and_limited(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['role' => 'customer']);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $this->actingAs($customer)->post(route('settings.avatar'), ['avatar' => UploadedFile::fake()->createWithContent('avatar.png', $png)])->assertRedirect();
        $path = $customer->fresh()->avatar;
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($customer)->delete(route('settings.avatar.remove'))->assertRedirect();
        $this->assertNull($customer->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
        $this->actingAs($customer)->post(route('settings.avatar'), ['avatar' => UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('avatar');
    }

    public function test_admin_reset_uses_broker_notification_without_replacing_password(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'password' => 'original-password']);
        $hash = $customer->password;

        $this->actingAs($admin)->post(route('admin.customers.reset-password', $customer))->assertRedirect();
        Notification::assertSentTo($customer, \Illuminate\Auth\Notifications\ResetPassword::class);
        $this->assertSame($hash, $customer->fresh()->password);
        $this->assertDatabaseHas('activity_logs', ['action' => 'password_reset_email_sent', 'subject_id' => $customer->id]);
    }

    public function test_support_tickets_are_owner_scoped_and_notify_both_sides(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $created = $this->actingAs($customer)->postJson(route('support.tickets.store'), ['subject' => 'Where is my order?', 'category' => 'shipping', 'message' => 'Please check'])->assertCreated()->json('data');
        $ticket = SupportTicket::query()->findOrFail($created['id']);

        $this->actingAs($other)->getJson(route('support.tickets.show', $ticket))->assertForbidden();
        $this->actingAs($admin)->postJson(route('admin.support.tickets.reply', $ticket), ['message' => 'We are checking it.'])->assertOk();
        $this->assertDatabaseHas('support_messages', ['ticket_id' => $ticket->id, 'sender_role' => 'admin']);
        $this->assertDatabaseHas('admin_notifications', ['user_id' => $customer->id, 'type' => 'support_admin_message']);

        $this->actingAs($admin)->patchJson(route('admin.support.tickets.status', $ticket), ['status' => 'closed'])->assertOk();
        $this->actingAs($admin)->patchJson(route('admin.support.tickets.status', $ticket), ['status' => 'open'])->assertOk();
        $this->actingAs($customer)->postJson(route('support.tickets.reply', $ticket), ['message' => 'Thanks'])->assertOk();
    }

    public function test_customer360_includes_bounded_crm_payload_and_customization_is_explicit_and_owned(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = $this->variant();
        $order = app(CreateOrder::class)->handle(new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]), ['user_id' => $customer->id, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => 'completed']);
        $item = $order->items()->first();

        $this->actingAs($customer)->postJson(route('customization-jobs.store'), ['order_id' => $order->id, 'order_item_id' => $item->id, 'customization_name' => 'HAI', 'customization_number' => '10', 'customization_notes' => 'Blue'])->assertCreated();
        $this->actingAs($customer)->postJson(route('customization-jobs.store'), ['order_id' => $order->id, 'order_item_id' => $item->id, 'customization_name' => 'OTHER'])->assertStatus(422);
        $this->actingAs($other)->postJson(route('customization-jobs.store'), ['order_id' => $order->id, 'order_item_id' => $item->id, 'customization_name' => 'NO'])->assertForbidden();
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'customization_name' => 'HAI', 'customization_number' => '10']);

        $payload = app(\App\Services\AdminIntelligenceService::class)->customer360($customer);
        $this->assertArrayHasKey('customer', $payload);
        $this->assertArrayHasKey('avatar', $payload);
        $this->assertArrayHasKey('active_personal_vouchers', $payload);
        $this->assertArrayHasKey('recent_orders', $payload);
        $this->assertArrayHasKey('recent_reviews', $payload);
        $this->assertArrayHasKey('recent_support_tickets', $payload);
        $this->assertCount(1, $payload['recent_orders']);
        $this->actingAs($admin)->getJson(route('admin.intelligence.customers.360', $customer))->assertOk();
    }

    public function test_customer360_eager_loads_recent_ticket_orders_for_the_bounded_payload(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant();
        $order = $this->customerOrder($customer, $variant, 'SUPPORT-ORDER', now())[0];
        SupportTicket::create([
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'subject' => 'Order question',
            'category' => 'order',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $tickets = app(\App\Services\AdminIntelligenceService::class)->customer360($customer)['recent_support_tickets'];

        $this->assertCount(1, $tickets);
        $this->assertTrue($tickets->first()->relationLoaded('order'));
        $this->assertSame($order->id, $tickets->first()->order->id);
    }

    public function test_customer_profile_renders_only_five_recent_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = $this->variant();

        foreach (range(1, 6) as $number) {
            $this->customerOrder($customer, $variant, 'ORDER-'.$number, now()->subDays(6 - $number));
        }

        $response = $this->actingAs($admin)->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertSee('ORDER-6');
        $response->assertDontSee('ORDER-1');
    }

    public function test_customer_profile_renders_only_five_recent_reviews(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = $this->variant();

        foreach (range(1, 6) as $number) {
            $createdAt = now()->subDays(6 - $number);
            [$order, $item] = $this->customerOrder($customer, $variant, 'REVIEW-ORDER-'.$number, $createdAt);
            Review::create([
                'user_id' => $customer->id,
                'product_id' => $variant->product_id,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'rating' => 5,
                'comment' => 'Review-'.$number,
                'status' => 'approved',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Review-6');
        $response->assertDontSee('Review-1');
    }

    public function test_customer360_completed_metrics_use_only_completed_non_refunded_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant();
        $this->customerOrder($customer, $variant, 'VALID-ORDER', now()->subDays(3), 'completed', 250000);
        $refunded = $this->customerOrder($customer, $variant, 'REFUNDED-ORDER', now()->subDays(2), 'completed', 900000)[0];
        $this->customerOrder($customer, $variant, 'PENDING-ORDER', now()->subDay(), 'pending', 800000);
        Payment::create([
            'order_id' => $refunded->id,
            'provider' => 'momo',
            'request_id' => str()->uuid(),
            'amount' => $refunded->total,
            'status' => 'paid',
            'refund_status' => 'refunded',
        ]);

        $payload = app(\App\Services\AdminIntelligenceService::class)->customer360($customer);

        $this->assertSame(3, $payload['total_orders']);
        $this->assertSame(1, $payload['completed_orders']);
        $this->assertSame(250000, $payload['completed_spend']);
        $this->assertSame(250000, $payload['average_order_value']);
    }

    private function customerOrder(User $customer, ProductVariant $variant, string $number, $createdAt, string $status = 'completed', int $total = 100000): array
    {
        $order = Order::create([
            'number' => $number,
            'user_id' => $customer->id,
            'subtotal' => $total,
            'discount' => 0,
            'shipping_fee' => 0,
            'total' => $total,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => $status,
            'recipient_name' => 'Buyer',
            'recipient_phone' => '0912345678',
            'recipient_email' => $customer->email,
            'province' => 'Ha Noi',
            'district' => 'Nam Tu Liem',
            'ward' => 'My Dinh',
            'address_line' => '1 Test Street',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'sku' => $variant->sku,
            'color' => $variant->color,
            'size' => $variant->size,
            'unit_price' => $total,
            'quantity' => 1,
        ]);

        return [$order, $item];
    }
}
