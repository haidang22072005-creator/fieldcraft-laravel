<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 5, int $price = 100000, ?Product $product = null, string $color = 'Trắng', string $size = 'M'): ProductVariant
    {
        $product ??= Product::create(['name' => 'Giày sân cỏ', 'slug' => 'giay-'.uniqid(), 'brand' => 'Fieldcraft', 'category' => 'Giày', 'is_active' => true]);
        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.uniqid(), 'color' => $color, 'size' => $size, 'price' => $price, 'stock' => $stock]);
    }

    private function add(ProductVariant $variant, int $quantity = 1)
    {
        return $this->postJson(route('cart.add'), ['product_variant_id' => $variant->id, 'quantity' => $quantity]);
    }

    public function test_guest_can_add_variant_to_cart(): void
    {
        $variant = $this->variant();
        $this->add($variant, 2)->assertOk()->assertJson(['count' => 2]);
        $this->assertEquals([['product_variant_id' => $variant->id, 'quantity' => 2]], session('cart'));
    }

    public function test_same_variant_combines_quantity(): void
    {
        $variant = $this->variant();
        $this->add($variant, 1); $this->add($variant, 2)->assertJson(['count' => 3]);
        $this->assertCount(1, session('cart'));
    }

    public function test_different_size_or_color_stays_separate(): void
    {
        $product = Product::create(['name'=>'Áo đấu','slug'=>'ao-dau','category'=>'Áo','is_active'=>true]);
        $medium = $this->variant(product: $product, color: 'Trắng', size: 'M');
        $large = $this->variant(product: $product, color: 'Trắng', size: 'L');
        $this->add($medium); $this->add($large);
        $this->assertCount(2, session('cart'));
    }

    public function test_quantity_cannot_exceed_stock(): void
    {
        $this->add($this->variant(stock: 2), 3)->assertUnprocessable()->assertJsonValidationErrors('quantity');
    }

    public function test_zero_and_negative_quantities_are_rejected(): void
    {
        $variant = $this->variant();
        $this->add($variant, 0)->assertUnprocessable();
        $this->add($variant, -1)->assertUnprocessable();
    }

    public function test_quantity_can_be_updated_in_session_cart(): void
    {
        $variant = $this->variant(); $this->add($variant);
        $this->putJson(route('cart.update', $variant), ['quantity' => 4])->assertOk()->assertJson(['count' => 4]);
        $this->assertSame(4, session('cart.0.quantity'));
    }

    public function test_item_can_be_removed(): void
    {
        $variant = $this->variant(); $this->add($variant);
        $this->deleteJson(route('cart.remove', $variant))->assertOk()->assertJson(['count' => 0]);
    }

    public function test_cart_can_be_cleared(): void
    {
        $this->add($this->variant()); $this->add($this->variant());
        $this->deleteJson(route('cart.clear'))->assertOk()->assertJson(['count' => 0]);
    }

    public function test_cart_never_reduces_stock(): void
    {
        $variant = $this->variant(stock: 5); $this->add($variant, 3);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_price_is_recalculated_from_database(): void
    {
        $variant = $this->variant(price: 345000);
        $this->postJson(route('cart.add'), ['product_variant_id'=>$variant->id,'quantity'=>2,'price'=>1])->assertJson(['subtotal'=>690000]);
        $this->get(route('cart.index'))->assertOk()->assertSee('690.000₫');
    }

    public function test_cart_badge_uses_server_quantity(): void
    {
        $this->add($this->variant(), 2);
        $this->get(route('store.home'))->assertOk()->assertSee('id="cartCount">2', false);
    }

    public function test_verified_customer_can_proceed_to_checkout(): void
    {
        $user = User::factory()->create(['role'=>'customer']); $this->actingAs($user);
        $this->add($this->variant());
        $this->get(route('checkout'))->assertOk()->assertSee('Thanh toán');
    }

    public function test_unverified_customer_is_blocked_from_checkout(): void
    {
        $user = User::factory()->unverified()->create(['role'=>'customer']); $this->actingAs($user);
        $this->add($this->variant());
        $this->get(route('checkout'))->assertRedirect(route('verification.notice'));
    }

    public function test_guest_cart_merges_into_database_after_login(): void
    {
        $variant = $this->variant(stock: 3); $this->add($variant, 2);
        $user = User::factory()->create(['role'=>'customer']);
        $this->post(route('login.store'), ['email'=>$user->email,'password'=>'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('cart_items', ['product_variant_id'=>$variant->id,'quantity'=>2]);
        $this->assertNull(session('cart'));
    }

    public function test_admin_cannot_use_cart(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'super-admin']));
        $this->add($this->variant())->assertForbidden();
        $this->get(route('cart.index'))->assertForbidden();
    }
}
