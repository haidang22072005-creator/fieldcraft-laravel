<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
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
}
