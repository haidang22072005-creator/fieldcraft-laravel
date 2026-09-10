<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CartSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock=5, int $price=100000, string $size='M'): ProductVariant
    {
        $product=Product::create(['name'=>'Sản phẩm '.$size,'slug'=>'sp-'.uniqid(),'category'=>'Giày','is_active'=>true]);
        return ProductVariant::create(['product_id'=>$product->id,'sku'=>'SKU-'.uniqid(),'color'=>'Đỏ','size'=>$size,'price'=>$price,'stock'=>$stock]);
    }

    private function user(): User { return User::factory()->create(['role'=>'customer']); }
    private function add(ProductVariant $variant,int $quantity=1){return $this->postJson(route('cart.add'),['product_variant_id'=>$variant->id,'quantity'=>$quantity]);}
    private function checkout(){return $this->post(route('checkout.store'),['recipient_name'=>'Test','recipient_phone'=>'0900000000','recipient_email'=>'test@example.com','province'=>'Đà Nẵng','district'=>'Hải Châu','ward'=>'Hải Châu I','address_line'=>'01 Nguyễn Văn Linh','payment_method'=>'cod']);}

    public function test_new_items_are_selected_by_default_for_guest_and_database_cart(): void
    {
        $guest=$this->variant();$this->add($guest);$this->assertTrue(session('cart.0.selected'));
        $this->actingAs($this->user());$db=$this->variant();$this->add($db);$this->assertDatabaseHas('cart_items',['product_variant_id'=>$db->id,'selected_for_checkout'=>true]);
    }

    public function test_guest_can_select_and_unselect_one_item(): void
    {
        $variant=$this->variant();$this->add($variant);
        $this->patchJson(route('cart.select',$variant),['selected'=>false])->assertOk()->assertJson(['selected_count'=>0,'selected_total'=>0]);
        $this->patchJson(route('cart.select',$variant),['selected'=>true])->assertJson(['selected_count'=>1]);
    }

    public function test_select_all_updates_all_items(): void
    {
        $a=$this->variant();$b=$this->variant();$this->add($a);$this->add($b);
        $this->patchJson(route('cart.select-all'),['selected'=>false])->assertJson(['selected_count'=>0]);
        $this->patchJson(route('cart.select-all'),['selected'=>true])->assertJson(['selected_count'=>2]);
    }

    public function test_unselected_item_is_excluded_and_quantity_recalculates_selected_total(): void
    {
        $a=$this->variant(price:100000);$b=$this->variant(price:250000);$this->add($a,2);$this->add($b);
        $this->patchJson(route('cart.select',$b),['selected'=>false])->assertJson(['subtotal'=>450000,'selected_total'=>200000]);
        $this->putJson(route('cart.update',$a),['quantity'=>3])->assertJson(['selected_total'=>300000]);
    }

    public function test_checkout_rejects_zero_selected_items(): void
    {
        $this->actingAs($this->user());$variant=$this->variant();$this->add($variant);$this->patchJson(route('cart.select',$variant),['selected'=>false]);
        $this->get(route('checkout'))->assertRedirect(route('cart.index'))->assertSessionHasErrors('cart');
    }

    public function test_checkout_creates_only_selected_items_and_keeps_unselected_items(): void
    {
        $this->actingAs($this->user());$selected=$this->variant(stock:5);$kept=$this->variant(stock:5);$this->add($selected,2);$this->add($kept);
        $this->patchJson(route('cart.select',$kept),['selected'=>false]);$this->checkout()->assertRedirect(route('store.home'));
        $this->assertDatabaseHas('order_items',['product_variant_id'=>$selected->id,'quantity'=>2]);$this->assertDatabaseMissing('order_items',['product_variant_id'=>$kept->id]);
        $this->assertDatabaseMissing('cart_items',['product_variant_id'=>$selected->id]);$this->assertDatabaseHas('cart_items',['product_variant_id'=>$kept->id,'selected_for_checkout'=>false]);
    }

    public function test_failed_order_transaction_keeps_every_cart_item(): void
    {
        $this->actingAs($this->user());$a=$this->variant();$b=$this->variant();$this->add($a);$this->add($b);OrderItem::creating(fn()=>throw new RuntimeException('fail'));
        try{$this->checkout();$this->fail('Expected failure');}catch(RuntimeException){}finally{OrderItem::flushEventListeners();}
        $this->assertDatabaseCount('cart_items',2);$this->assertSame(5,$a->fresh()->stock);$this->assertSame(5,$b->fresh()->stock);
    }

    public function test_selected_item_stock_is_revalidated_and_out_of_stock_cannot_checkout(): void
    {
        $this->actingAs($this->user());$variant=$this->variant(stock:2);$this->add($variant,2);$variant->update(['stock'=>1]);
        $this->get(route('checkout'))->assertRedirect(route('cart.index'))->assertSessionHasErrors('cart');
        $this->patchJson(route('cart.select',$variant),['selected'=>true])->assertUnprocessable();
    }

    public function test_guest_session_selection_survives_login_merge(): void
    {
        $variant=$this->variant();$this->add($variant);$this->patchJson(route('cart.select',$variant),['selected'=>false]);$user=$this->user();
        $this->post(route('login.store'),['email'=>$user->email,'password'=>'password'])->assertRedirect();
        $this->assertDatabaseHas('cart_items',['product_variant_id'=>$variant->id,'selected_for_checkout'=>false]);
    }
}
