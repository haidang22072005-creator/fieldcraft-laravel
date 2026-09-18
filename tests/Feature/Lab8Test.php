<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Lab8Test extends TestCase
{
    use RefreshDatabase;

    private function order(User $customer, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'number' => 'FC-'.str()->upper(str()->random(8)),
            'user_id' => $customer->id,
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_fee' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'shipping_status' => 'pending',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone ?: '0912345678',
            'recipient_email' => $customer->email,
            'province' => 'Ha Noi',
            'district' => 'Nam Tu Liem',
            'ward' => 'My Dinh',
            'address_line' => '1 Test Street',
        ], $attributes));
    }

    private function productItem(Order $order, string $category = 'Giày', int $quantity = 2, int $unitPrice = 50000): void
    {
        $product = Product::create([
            'name' => 'Lab 8 '.$category.' '.str()->random(6),
            'slug' => 'lab8-'.str()->random(12),
            'category' => $category,
            'brand' => 'FIELDCRAFT',
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'L8-'.str()->upper(str()->random(8)),
            'color' => 'Đen',
            'size' => '42',
            'price' => $unitPrice,
            'stock' => 10,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'sku' => $variant->sku,
            'color' => 'Đen',
            'size' => '42',
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
        ]);
    }

    public function test_admin_order_list_supports_search_filters_sort_and_bounded_page_sizes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'phone' => '0900000001']);
        $matching = $this->order($customer, [
            'number' => 'ORD-LAB8-MATCH',
            'status' => 'shipping',
            'shipping_status' => 'delivering',
            'payment_method' => 'bank_qr',
            'payment_status' => 'paid',
            'ghn_order_code' => 'GHN-LAB8-001',
            'total' => 300000,
        ]);
        $other = $this->order($customer, [
            'number' => 'ORD-LAB8-OTHER',
            'status' => 'pending',
            'total' => 100000,
        ]);
        $this->productItem($matching, 'Bóng');

        $response = $this->actingAs($admin)->get(route('admin.orders.index', [
            'q' => 'GHN-LAB8-001',
            'shipping_status' => 'delivering',
            'payment_method' => 'bank_qr',
            'sort' => 'total_asc',
            'per_page' => 25,
        ]))->assertOk();

        $orders = $response->viewData('orders');
        $this->assertSame(25, $orders->perPage());
        $this->assertTrue($orders->getCollection()->contains('id', $matching->id));
        $this->assertFalse($orders->getCollection()->contains('id', $other->id));

        $this->actingAs($admin)->get(route('admin.orders.index', ['per_page' => 50]))->assertOk()->tap(function ($response): void {
            $this->assertSame(50, $response->viewData('orders')->perPage());
        });
        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => '0900000001']))->assertOk()->tap(function ($response) use ($matching): void {
            $this->assertTrue($response->viewData('orders')->getCollection()->contains('id', $matching->id));
        });
        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_reports_use_valid_revenue_once_and_expose_real_aggregations(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 18, 12));
        try {
            $admin = User::factory()->create(['role' => 'admin']);
            $customer = User::factory()->create(['role' => 'customer']);
            $cod = $this->order($customer, ['number' => 'ORD-LAB8-COD', 'status' => 'completed', 'total' => 100000, 'created_at' => now()->subDays(2)]);
            $momo = $this->order($customer, ['number' => 'ORD-LAB8-MOMO', 'status' => 'completed', 'payment_method' => 'momo', 'payment_status' => 'paid', 'total' => 200000, 'created_at' => now()->subDay()]);
            $bank = $this->order($customer, ['number' => 'ORD-LAB8-BANK', 'status' => 'completed', 'payment_method' => 'bank_qr', 'payment_status' => 'paid', 'total' => 300000]);
            $failed = $this->order($customer, ['status' => 'completed', 'payment_method' => 'momo', 'payment_status' => 'failed', 'total' => 900000]);
            $refunded = $this->order($customer, ['status' => 'completed', 'payment_method' => 'bank_qr', 'payment_status' => 'paid', 'total' => 800000]);
            $this->order($customer, ['status' => 'cancelled', 'total' => 700000]);
            $this->productItem($cod, 'Giày', 2, 50000);
            $this->productItem($momo, 'Áo', 1, 200000);
            Payment::create(['order_id' => $failed->id, 'provider' => 'momo', 'request_id' => str()->uuid(), 'amount' => 900000, 'status' => 'failed']);
            Payment::create(['order_id' => $refunded->id, 'provider' => 'bank_qr', 'request_id' => str()->uuid(), 'amount' => 800000, 'status' => 'paid', 'refund_status' => 'refunded', 'refunded_at' => now()]);
            Payment::create(['order_id' => $refunded->id, 'provider' => 'bank_qr', 'request_id' => str()->uuid(), 'amount' => 800000, 'status' => 'failed']);

            $page = $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
            $page->assertSee('BÁO CÁO & PHÂN TÍCH', false)->assertSee('600.000', false)->assertSee('Doanh thu theo danh mục', false);
            $charts = $this->actingAs($admin)->getJson(route('admin.reports.charts'))->assertOk();
            $charts->assertJsonPath('data.summary.valid_revenue', 600000);
            $charts->assertJsonPath('data.summary.total_orders', 6);
            $charts->assertJsonPath('data.payment_methods.momo.revenue', 200000);
            $charts->assertJsonPath('data.payment_methods.bank_qr.revenue', 300000);
            $charts->assertJsonPath('data.payment_methods.cod.revenue', 100000);
            $this->assertSame(2, collect($charts->json('data.categories'))->where('category', 'Giày')->value('quantity'));
            $this->assertSame(1, collect($charts->json('data.categories'))->where('category', 'Áo')->value('quantity'));
            $this->assertSame(1, collect($charts->json('data.daily'))->where('period', '2026-09-16')->value('order_count'));
            $this->assertSame(3, collect($charts->json('data.monthly'))->where('period', '2026-09')->value('order_count'));
            $this->assertSame(3, collect($charts->json('data.yearly'))->where('period', '2026')->value('order_count'));
            $this->actingAs($customer)->get(route('admin.reports.index'))->assertForbidden();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_account_list_shows_user_metadata_and_customer360_link_without_plaintext_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->unverified()->create(['role' => 'customer', 'phone' => '0900000099']);

        $response = $this->actingAs($admin)->get(route('admin.accounts.index', ['q' => '0900000099']))->assertOk();
        $response->assertSee($customer->name)->assertSee($customer->phone)->assertSee(route('admin.customers.show', $customer), false)->assertSee('Chưa xác thực', false);
        $this->assertStringNotContainsString($customer->password, $response->getContent());
        $this->assertTrue(Hash::check('password', $customer->password));
    }
}
