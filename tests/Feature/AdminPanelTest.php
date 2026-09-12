<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user, string $status = 'pending'): Order
    {
        return Order::create([
            'number' => 'FC-'.str()->upper(str()->random(8)), 'user_id' => $user->id, 'subtotal' => 100000, 'discount' => 0,
            'shipping_fee' => 0, 'total' => 100000, 'payment_method' => 'cod', 'payment_status' => 'pending', 'status' => $status,
            'recipient_name' => 'Buyer', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email, 'province' => 'Ha Noi',
            'district' => 'Nam Tu Liem', 'ward' => 'My Dinh 1', 'address_line' => '1 Test Street',
        ]);
    }

    public function test_dashboard_uses_real_completed_revenue_and_admin_can_view_order_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order($customer, 'completed');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('100,000');
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee($order->number)->assertSee('Buyer');
        $this->actingAs($admin)->get(route('admin.accounts.index'))->assertOk()->assertSee($customer->email);
    }

    public function test_only_super_admin_can_manage_staff_and_password_is_hashed(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin)->patch(route('admin.accounts.role', $target), ['role' => 'admin'])->assertForbidden();
        $this->actingAs($superAdmin)->post(route('admin.accounts.store'), ['name' => 'Staff', 'email' => 'staff@example.test', 'password' => 'secret123', 'password_confirmation' => 'secret123'])->assertRedirect();
        $staff = User::where('email', 'staff@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $staff->password));
        $this->actingAs($superAdmin)->patch(route('admin.accounts.role', $target), ['role' => 'admin'])->assertRedirect();
        $this->assertSame('admin', $target->fresh()->role);
    }

    public function test_manual_completion_records_actor_once_in_testing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order(User::factory()->create(), 'shipping');

        $this->actingAs($admin)->post(route('admin.orders.manual-complete', $order))->assertRedirect();
        $saved = $order->fresh();
        $this->assertSame('completed', $saved->status);
        $this->assertSame($admin->id, $saved->completed_by);
        $this->assertNotNull($saved->completed_at);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => 'completed', 'actor_id' => $admin->id]);
        $this->actingAs($admin)->post(route('admin.orders.manual-complete', $order))->assertRedirect();
        $this->assertDatabaseCount('order_status_histories', 1);
    }
}
