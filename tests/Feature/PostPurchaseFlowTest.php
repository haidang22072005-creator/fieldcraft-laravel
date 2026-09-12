<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PostPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.ghn.token', 'test-token');
        config()->set('services.ghn.shop_id', '219637');
        config()->set('services.ghn.from_district_id', 3440);
        config()->set('services.ghn.from_ward_code', '13004');
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $attributes));
    }

    private function variant(): ProductVariant
    {
        $product = Product::create(['name' => 'Review Boot', 'slug' => 'review-'.uniqid(), 'category' => 'Giày', 'is_active' => true]);
        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'REV-'.uniqid(), 'color' => 'Đỏ', 'size' => '42', 'price' => 100000, 'stock' => 10]);
    }

    private function order(User $user, ProductVariant $variant, string $status = 'pending'): Order
    {
        return app(CreateOrder::class)->handle(new Collection([
            ['product_variant_id' => $variant->id, 'quantity' => 1],
        ]), [
            'user_id' => $user->id,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => $status,
            'shipping_fee' => 0,
            'recipient_name' => 'Review Buyer',
            'recipient_phone' => '0912345678',
            'recipient_email' => $user->email,
            'province' => 'Ha Noi',
            'district' => 'Nam Tu Liem',
            'ward' => 'My Dinh 1',
            'address_line' => '1 Test Street',
            'to_district_id' => 1600,
            'to_ward_code' => '00001',
        ]);
    }

    public function test_product_payload_maps_color_images_and_variant_values(): void
    {
        $variant = $this->variant();
        ProductImage::create(['product_id' => $variant->product_id, 'color' => 'Đỏ', 'path' => 'images/products/review.svg', 'position' => 1]);
        $variant->product->images()->create(['color' => 'Xanh', 'path' => 'images/products/review-blue.svg', 'position' => 2]);
        $variant->product->variants()->create(['sku' => 'REV-BLUE-42', 'color' => 'Xanh', 'size' => '42', 'price' => 110000, 'stock' => 3]);

        $this->getJson(route('store.products'))
            ->assertOk()
            ->assertJsonFragment(['sku' => $variant->sku, 'color' => 'Đỏ', 'size' => '42', 'price' => 100000, 'stock' => 10])
            ->assertJsonFragment(['sku' => 'REV-BLUE-42', 'color' => 'Xanh', 'size' => '42', 'price' => 110000, 'stock' => 3])
            ->assertJsonFragment(['url' => asset('images/products/review.svg'), 'color' => 'Đỏ'])
            ->assertJsonFragment(['url' => asset('images/products/review-blue.svg'), 'color' => 'Xanh']);
    }

    public function test_admin_order_lifecycle_is_locked(): void
    {
        Http::fake(['*v2/shipping-order/create' => Http::response(['code' => 200, 'data' => ['order_code' => 'GHN-LIFECYCLE', 'total_fee' => 35000]])]);
        $admin = $this->user(['role' => 'admin']);
        $order = $this->order($this->user(), $this->variant());

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'confirmed'])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'packing'])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'shipping'])->assertRedirect();
        $this->assertSame('shipping', $order->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'pending'])->assertSessionHasErrors('status');
    }

    public function test_ghn_delivered_sync_completes_order(): void
    {
        Http::fake(['*v2/shipping-order/detail' => Http::response(['code' => 200, 'data' => ['status' => 'delivered']])]);
        $admin = $this->user(['role' => 'admin']);
        $order = $this->order($this->user(), $this->variant(), 'shipping');
        $order->update(['ghn_order_code' => 'GHN-DELIVERED', 'shipping_status' => 'transporting']);

        $this->actingAs($admin)->post(route('admin.orders.sync-ghn', $order))->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed', 'shipping_status' => 'delivered']);
    }

    public function test_only_owner_completed_order_can_review_once(): void
    {
        $owner = $this->user();
        $order = $this->order($owner, $this->variant(), 'completed');
        $item = $order->items->first();

        $this->actingAs($owner)->postJson(route('purchases.review.store', [$order, $item]), ['rating' => 5, 'comment' => 'Great'])->assertCreated();
        $this->actingAs($owner)->postJson(route('purchases.review.store', [$order, $item]), ['rating' => 4])->assertStatus(422);
        $this->assertDatabaseHas('reviews', ['order_item_id' => $item->id, 'status' => 'pending']);

        $this->actingAs($this->user())->postJson(route('purchases.review.store', [$order, $item]), ['rating' => 5])->assertForbidden();
    }

    public function test_pending_or_shipping_order_cannot_review(): void
    {
        foreach (['pending', 'shipping'] as $status) {
            $owner = $this->user();
            $order = $this->order($owner, $this->variant(), $status);
            $this->actingAs($owner)->postJson(route('purchases.review.store', [$order, $order->items->first()]), ['rating' => 5])->assertStatus(422);
        }
    }

    public function test_admin_can_moderate_reply_and_public_only_shows_approved_reviews(): void
    {
        $admin = $this->user(['role' => 'admin']);
        $owner = $this->user();
        $order = $this->order($owner, $this->variant(), 'completed');
        $item = $order->items->first();
        $this->actingAs($owner)->post(route('purchases.review.store', [$order, $item]), ['rating' => 5, 'comment' => 'Visible'])->assertRedirect();
        $review = Review::firstOrFail();

        $this->actingAs($admin)->patch(route('admin.reviews.status', $review), ['status' => 'approved'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.reviews.reply', $review), ['reply' => 'Cảm ơn bạn từ FIELDCRAFT.'])->assertRedirect();

        $this->getJson(route('store.products'))
            ->assertOk()
            ->assertJsonPath('data.0.reviewCount', 1)
            ->assertJsonPath('data.0.reviews.0.officialReply.label', 'FIELDCRAFT');

        $hidden = Review::create([
            'user_id' => $owner->id, 'product_id' => $review->product_id, 'order_id' => $order->id,
            'order_item_id' => $order->items()->create(['product_variant_id' => null, 'product_name' => 'Other', 'sku' => 'OTHER', 'color' => 'N/A', 'size' => 'N/A', 'unit_price' => 1, 'quantity' => 1])->id,
            'rating' => 1, 'comment' => 'Hidden', 'status' => 'pending',
        ]);
        $this->actingAs($admin)->patch(route('admin.reviews.status', $hidden), ['status' => 'hidden'])->assertRedirect();
        $this->getJson(route('store.products'))->assertJsonMissing(['comment' => 'Hidden']);
    }
}
