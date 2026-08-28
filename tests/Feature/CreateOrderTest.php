<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 10, int $price = 100000): ProductVariant
    {
        $key = (string) Str::uuid();
        $product = Product::query()->create(['name'=>'Test Boot','slug'=>$key,'brand'=>'Fieldcraft','category'=>'Giày đinh','is_active'=>true]);

        return $product->variants()->create(['sku'=>'SKU-'.$key,'color'=>'Đen','size'=>'40','price'=>$price,'stock'=>$stock]);
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::query()->create(array_merge(['code'=>'SAVE10','type'=>'percent','value'=>10,'minimum_order_value'=>0,'usage_limit'=>10,'used_count'=>0,'expires_at'=>now()->addDay(),'is_active'=>true], $overrides));
    }

    private function createOrder(ProductVariant $variant, ?string $couponCode = null, int $quantity = 1): Order
    {
        return app(CreateOrder::class)->handle(
            new Collection([['product_variant_id'=>$variant->id,'quantity'=>$quantity]]),
            ['user_id'=>User::factory()->create()->id,'payment_method'=>'cod','payment_status'=>'pending','status'=>'pending','shipping_fee'=>0,'coupon_code'=>$couponCode]
        );
    }

    public function test_order_code_matches_required_format(): void
    {
        $order = $this->createOrder($this->variant());
        $this->assertMatchesRegularExpression('/^ORD\d{8}\d{8}$/', $order->number);
    }

    public function test_two_orders_receive_different_codes(): void
    {
        $variant = $this->variant();
        $first = $this->createOrder($variant);
        $second = $this->createOrder($variant);
        $this->assertNotSame($first->number, $second->number);
    }

    public function test_order_code_uses_actual_order_id(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 28));
        $order = $this->createOrder($this->variant());
        $this->assertSame('ORD20260828'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT), $order->number);
    }

    public function test_order_numbering_does_not_use_count_or_max(): void
    {
        $source = file_get_contents(app_path('Actions/CreateOrder.php'));
        $this->assertStringNotContainsString('Order::query()->count()', $source);
        $this->assertDoesNotMatchRegularExpression('/Order[^;]*(?:max|count)\s*\(/i', $source);
    }

    public function test_valid_coupon_increments_usage_exactly_once(): void
    {
        $coupon = $this->coupon();
        $order = $this->createOrder($this->variant(price: 200000), $coupon->code);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame(20000, $order->discount);
        $this->assertSame($coupon->id, $order->coupon_id);
    }

    public function test_failed_checkout_does_not_increment_coupon(): void
    {
        $coupon = $this->coupon(['expires_at'=>now()->subMinute()]);
        try { $this->createOrder($this->variant(), $coupon->code); $this->fail('Expected coupon validation failure.'); }
        catch (ValidationException) {}
        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_rollback_preserves_coupon_and_stock_consistency(): void
    {
        $variant = $this->variant(stock: 5);
        $coupon = $this->coupon();
        OrderItem::creating(fn () => throw new RuntimeException('Forced order item failure'));

        try { $this->createOrder($variant, $coupon->code, 2); $this->fail('Expected forced failure.'); }
        catch (RuntimeException) {}
        finally { OrderItem::flushEventListeners(); }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    public function test_coupon_usage_limit_is_enforced(): void
    {
        $variant = $this->variant(stock: 5);
        $coupon = $this->coupon(['usage_limit'=>1,'used_count'=>1]);
        try { $this->createOrder($variant, $coupon->code); $this->fail('Expected usage limit failure.'); }
        catch (ValidationException) {}
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }
}
