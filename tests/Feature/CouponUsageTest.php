<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class CouponUsageTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 20): ProductVariant
    {
        $key = (string) Str::uuid();
        $product = Product::create(['name'=>'Test Ball','slug'=>$key,'category'=>'Bóng','is_active'=>true]);
        return ProductVariant::create(['product_id'=>$product->id,'sku'=>'SKU-'.$key,'color'=>'Trắng','size'=>'5','price'=>100000,'stock'=>$stock]);
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge(['code'=>'ONE10','type'=>'percent','value'=>10,'minimum_order_value'=>0,'usage_limit'=>10,'per_user_limit'=>1,'used_count'=>0,'expires_at'=>now()->addDay(),'is_active'=>true], $overrides));
    }

    private function order(ProductVariant $variant, User $user, Coupon $coupon): Order
    {
        return app(CreateOrder::class)->handle(new Collection([['product_variant_id'=>$variant->id,'quantity'=>1]]), ['user_id'=>$user->id,'payment_method'=>'cod','status'=>'pending','payment_status'=>'pending','shipping_fee'=>0,'coupon_code'=>$coupon->code]);
    }

    public function test_first_coupon_use_succeeds_and_creates_one_usage(): void
    {
        $coupon=$this->coupon(); $user=User::factory()->create(); $order=$this->order($this->variant(),$user,$coupon);
        $this->assertDatabaseHas('coupon_usages',['coupon_id'=>$coupon->id,'user_id'=>$user->id,'order_id'=>$order->id]);
        $this->assertSame(1,$coupon->fresh()->used_count);
        $this->assertDatabaseCount('coupon_usages',1);
    }

    public function test_successful_order_creates_exactly_one_usage(): void
    {
        $coupon=$this->coupon(); $user=User::factory()->create(); $order=$this->order($this->variant(),$user,$coupon);
        $this->assertSame(1,$order->couponUsage()->count());
        $this->assertSame($order->id,$coupon->usages()->sole()->order_id);
    }

    public function test_same_user_is_blocked_after_per_user_limit(): void
    {
        $coupon=$this->coupon(); $user=User::factory()->create(); $variant=$this->variant(); $this->order($variant,$user,$coupon);
        $this->expectException(ValidationException::class); $this->order($variant,$user,$coupon);
    }

    public function test_different_user_can_still_use_coupon(): void
    {
        $coupon=$this->coupon(); $variant=$this->variant();
        $this->order($variant,User::factory()->create(),$coupon); $this->order($variant,User::factory()->create(),$coupon);
        $this->assertSame(2,$coupon->fresh()->used_count); $this->assertDatabaseCount('coupon_usages',2);
    }

    public function test_total_usage_limit_is_enforced(): void
    {
        $coupon=$this->coupon(['usage_limit'=>1]); $variant=$this->variant(); $this->order($variant,User::factory()->create(),$coupon);
        try{$this->order($variant,User::factory()->create(),$coupon);$this->fail('Expected limit failure.');}catch(ValidationException){}
        $this->assertSame(1,$coupon->fresh()->used_count); $this->assertDatabaseCount('coupon_usages',1);
    }

    public function test_failed_checkout_creates_no_usage(): void
    {
        $coupon=$this->coupon(['expires_at'=>now()->subMinute()]);
        try{$this->order($this->variant(),User::factory()->create(),$coupon);$this->fail('Expected failure.');}catch(ValidationException){}
        $this->assertDatabaseCount('coupon_usages',0); $this->assertSame(0,$coupon->fresh()->used_count);
    }

    public function test_rollback_keeps_usage_count_order_and_stock_consistent(): void
    {
        $coupon=$this->coupon(); $variant=$this->variant(stock:5); OrderItem::creating(fn()=>throw new RuntimeException('fail'));
        try{$this->order($variant,User::factory()->create(),$coupon);$this->fail('Expected rollback.');}catch(RuntimeException){}finally{OrderItem::flushEventListeners();}
        $this->assertDatabaseCount('orders',0); $this->assertDatabaseCount('coupon_usages',0); $this->assertSame(0,$coupon->fresh()->used_count); $this->assertSame(5,$variant->fresh()->stock);
    }

    public function test_duplicate_usage_for_same_order_is_prevented(): void
    {
        $coupon=$this->coupon(); $user=User::factory()->create(); $order=$this->order($this->variant(),$user,$coupon);
        $this->expectException(QueryException::class);
        CouponUsage::create(['coupon_id'=>$coupon->id,'user_id'=>$user->id,'order_id'=>$order->id,'used_at'=>now()]);
    }

    public function test_cancellation_restores_coupon_usage_exactly_once(): void
    {
        $coupon=$this->coupon(); $order=$this->order($this->variant(),User::factory()->create(),$coupon); $admin=User::factory()->create(['role'=>'super-admin']);
        $this->actingAs($admin)->patch(route('admin.orders.status',$order),['status'=>'cancelled'])->assertRedirect();
        $this->assertSame(0,$coupon->fresh()->used_count); $this->assertDatabaseCount('coupon_usages',0);
        $this->patch(route('admin.orders.status',$order),['status'=>'cancelled'])->assertRedirect();
        $this->assertSame(0,$coupon->fresh()->used_count); $this->assertDatabaseCount('coupon_usages',0);
    }
}
