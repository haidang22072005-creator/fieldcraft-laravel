<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AdminIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalPaymentSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_online_orders_cannot_be_cancelled_and_side_effects_are_preserved(): void
    {
        foreach (['momo', 'payos', 'bank_qr'] as $method) {
            $user = User::factory()->create(['role' => 'customer']);
            $admin = User::factory()->create(['role' => 'admin']);
            $variant = $this->variant($method);
            $order = $this->order($user, $variant, $method);
            $coupon = Coupon::create([
                'code' => strtoupper($method).'10', 'type' => 'percent', 'value' => 10,
                'minimum_order_value' => 0, 'usage_limit' => 10, 'used_count' => 1, 'is_active' => true,
            ]);
            $order->update(['coupon_id' => $coupon->id]);
            CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $user->id, 'order_id' => $order->id, 'used_at' => now()]);
            $beforeStock = $variant->fresh()->stock;

            if ($method === 'momo') {
                $this->actingAs($user)->postJson(route('purchases.cancel', $order))
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors(['order'])
                    ->assertJsonPath('errors.order.0', 'Đơn đã thanh toán trực tuyến. Vui lòng yêu cầu hoàn tiền.');
            } else {
                $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])
                    ->assertSessionHasErrors('order');
            }

            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending', 'payment_status' => 'paid']);
            $this->assertSame($beforeStock, $variant->fresh()->stock);
            $this->assertDatabaseHas('coupon_usages', ['order_id' => $order->id]);
            $this->assertSame(1, $coupon->fresh()->used_count);
        }
    }

    public function test_refund_workflow_is_visible_and_only_confirmed_refunds_count_as_refunded(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $variant = $this->variant('refund');
        $order = $this->order($user, $variant, 'momo');
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $payment->update(['refund_status' => 'required', 'refund_reason' => 'Late success after cancellation.']);

        $service = app(AdminIntelligenceService::class);
        $this->assertContains('refund_required', $service->opsRadar()->pluck('type')->all());
        $finance = $service->finance();
        $this->assertSame(1, $finance['refund_required_count']);
        $this->assertSame((int) $payment->amount, $finance['refund_required_amount']);
        $this->assertSame(0, $finance['refunded_amount']);

        $payment->update(['refund_status' => 'refunded', 'refunded_at' => now()]);
        $finance = $service->finance();
        $this->assertSame((int) $payment->amount, $finance['refunded_amount']);
        $this->assertSame(0, $finance['refund_required_count']);
    }

    private function variant(string $suffix): ProductVariant
    {
        $product = Product::create(['name' => 'Safety Boot '.$suffix, 'slug' => 'safety-'.$suffix.'-'.str()->random(6), 'category' => 'Giày']);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'SAFE-'.str()->random(8), 'color' => 'Đen', 'size' => '42',
            'price' => 100000, 'stock' => 0,
        ]);
    }

    private function order(User $user, ProductVariant $variant, string $method): Order
    {
        $order = Order::create([
            'number' => 'SAFE-'.str()->upper(str()->random(8)), 'user_id' => $user->id,
            'subtotal' => 100000, 'discount' => 0, 'shipping_fee' => 0, 'total' => 100000,
            'payment_method' => $method, 'payment_status' => 'paid', 'status' => 'pending',
            'recipient_name' => 'Buyer', 'recipient_phone' => '0912345678', 'recipient_email' => $user->email,
            'province' => 'Ha Noi', 'district' => 'Nam Tu Liem', 'ward' => 'My Dinh 1', 'address_line' => '1 Test Street',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_variant_id' => $variant->id, 'product_name' => $variant->product->name,
            'sku' => $variant->sku, 'color' => $variant->color, 'size' => $variant->size,
            'unit_price' => $variant->price, 'quantity' => 1,
        ]);
        Payment::create([
            'order_id' => $order->id, 'provider' => $method, 'request_id' => (string) str()->uuid(),
            'provider_order_id' => 'SAFE-'.$order->id, 'amount' => $order->total, 'status' => 'paid',
        ]);

        return $order;
    }
}
