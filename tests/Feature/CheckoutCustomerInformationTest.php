<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutCustomerInformationTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $attributes));
    }

    private function variant(int $stock = 5): ProductVariant
    {
        $product = Product::create(['name' => 'Giày kiểm thử', 'slug' => 'checkout-'.uniqid(), 'category' => 'Giày', 'is_active' => true]);
        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'CHK-'.uniqid(), 'color' => 'Đen', 'size' => '42', 'price' => 750000, 'stock' => $stock]);
    }

    private function prepareCart(User $user): ProductVariant
    {
        $variant = $this->variant();
        $this->actingAs($user)->postJson(route('cart.add'), ['product_variant_id' => $variant->id, 'quantity' => 1])->assertOk();
        return $variant;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'recipient_name' => 'Nguyễn Văn An',
            'recipient_phone' => '0912345678',
            'recipient_email' => 'an@example.com',
            'province' => 'Đà Nẵng',
            'district' => 'Hải Châu',
            'ward' => 'Hải Châu I',
            'address_line' => '01 Nguyễn Văn Linh',
            'note' => 'Giao giờ hành chính',
            'payment_method' => 'cod',
        ], $overrides);
    }

    public function test_checkout_form_contains_complete_customer_information_fields(): void
    {
        $user = $this->user();
        $this->prepareCart($user);

        $this->get(route('checkout'))->assertOk()
            ->assertSee('name="recipient_name"', false)->assertSee('name="recipient_phone"', false)
            ->assertSee('name="recipient_email"', false)->assertSee('name="province"', false)
            ->assertSee('name="district"', false)->assertSee('name="ward"', false)
            ->assertSee('name="address_line"', false)->assertSee('name="note"', false);
    }

    public function test_checkout_prefills_saved_user_information(): void
    {
        $user = $this->user(['name' => 'Khách Fieldcraft', 'email' => 'khach@example.com', 'phone' => '0987654321']);
        $this->prepareCart($user);

        $this->get(route('checkout'))->assertOk()
            ->assertSee('value="Khách Fieldcraft"', false)
            ->assertSee('value="khach@example.com"', false)
            ->assertSee('value="0987654321"', false);
    }

    public function test_required_customer_fields_are_validated(): void
    {
        $user = $this->user();
        $this->prepareCart($user);

        $this->post(route('checkout.store'), $this->validPayload([
            'recipient_name' => '', 'province' => '', 'district' => '', 'ward' => '', 'address_line' => '',
        ]))->assertSessionHasErrors(['recipient_name', 'province', 'district', 'ward', 'address_line']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_phone_and_email_formats_are_validated(): void
    {
        $user = $this->user();
        $this->prepareCart($user);

        $this->post(route('checkout.store'), $this->validPayload([
            'recipient_phone' => '12345', 'recipient_email' => 'khong-phai-email',
        ]))->assertSessionHasErrors(['recipient_phone', 'recipient_email']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_valid_checkout_stores_customer_snapshot_and_cod(): void
    {
        $user = $this->user();
        $this->prepareCart($user);

        $this->post(route('checkout.store'), $this->validPayload())->assertRedirect(route('store.home'));

        $this->assertDatabaseHas('orders', array_merge(
            ['user_id' => $user->id, 'payment_method' => 'cod'],
            collect($this->validPayload())->except('payment_method')->all()
        ));
    }

    public function test_order_snapshot_does_not_change_when_user_profile_changes(): void
    {
        $user = $this->user();
        $this->prepareCart($user);
        $this->post(route('checkout.store'), $this->validPayload());
        $order = Order::latest('id')->firstOrFail();

        $user->forceFill(['name' => 'Tên đã đổi', 'email' => 'moi@example.com', 'phone' => '0909999999'])->save();

        $this->assertSame('Nguyễn Văn An', $order->fresh()->recipient_name);
        $this->assertSame('an@example.com', $order->fresh()->recipient_email);
        $this->assertSame('0912345678', $order->fresh()->recipient_phone);
    }
}
