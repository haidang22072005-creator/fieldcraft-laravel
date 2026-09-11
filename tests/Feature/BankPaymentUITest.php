<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankPaymentUITest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function variant(): ProductVariant
    {
        $product = Product::create([
            'name' => 'Áo Thun Fieldcraft',
            'slug' => 'ao-thun-' . uniqid(),
            'category' => 'Áo',
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TS-' . uniqid(),
            'color' => 'Đen',
            'size' => 'L',
            'price' => 250000,
            'stock' => 10,
        ]);
    }

    public function test_checkout_page_displays_bank_qr_payment_option_and_cta(): void
    {
        $user = $this->customer();
        $variant = $this->variant();

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $response = $this->actingAs($user)->get(route('checkout'));
        $response->assertOk();
        $response->assertSee('value="bank_qr"', false);
        $response->assertSee('Chuyển khoản ngân hàng');
        $response->assertSee('Quét VietQR bằng ứng dụng ngân hàng của bạn');
        $response->assertSee('VIETQR');
    }

    public function test_bank_qr_payment_page_renders_pending_state_with_details(): void
    {
        $user = $this->customer();
        $order = Order::create([
            'user_id' => $user->id,
            'number' => 'ORD202609110001',
            'subtotal' => 250000,
            'shipping_fee' => 30000,
            'discount' => 0,
            'total' => 280000,
            'payment_method' => 'bank_qr',
            'payment_status' => 'pending',
            'status' => 'pending_payment',
            'recipient_name' => 'Nguyễn Văn An',
            'recipient_phone' => '0901234567',
            'recipient_email' => 'an@example.com',
            'province' => 'Hà Nội',
            'district' => 'Nam Từ Liêm',
            'ward' => 'Mỹ Đình 1',
            'address_line' => '123 Đường Mới',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'bank_qr',
            'request_id' => 'req-001',
            'provider_order_id' => '260911120000000001',
            'amount' => 280000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('payments.bank', $order));
        $response->assertOk();
        $response->assertSee('THANH TOÁN CHUYỂN KHOẢN');
        $response->assertSee('VIETQR • THANH TOÁN NGÂN HÀNG');
        $response->assertSee($order->number);
        $response->assertSee('280.000₫');
        $response->assertSee('MỞ APP NGÂN HÀNG VÀ QUÉT MÃ');
        $response->assertSee('FC0000001');
    }

    public function test_bank_qr_payment_page_enforces_ownership(): void
    {
        $owner = $this->customer();
        $otherUser = $this->customer();

        $order = Order::create([
            'user_id' => $owner->id,
            'number' => 'ORD202609110002',
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'bank_qr',
            'payment_status' => 'pending',
            'status' => 'pending_payment',
            'recipient_name' => 'Owner',
            'recipient_phone' => '0901234567',
            'recipient_email' => 'owner@example.com',
            'province' => 'HN',
            'district' => 'NTL',
            'ward' => 'MD',
            'address_line' => '123',
        ]);

        $this->actingAs($otherUser)
            ->get(route('payments.bank', $order))
            ->assertForbidden();
    }

    public function test_bank_qr_payment_page_renders_paid_state(): void
    {
        $user = $this->customer();
        $order = Order::create([
            'user_id' => $user->id,
            'number' => 'ORD202609110003',
            'subtotal' => 250000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 250000,
            'payment_method' => 'bank_qr',
            'payment_status' => 'paid',
            'status' => 'pending',
            'recipient_name' => 'Nguyễn An',
            'recipient_phone' => '0901234567',
            'recipient_email' => 'an@example.com',
            'province' => 'HN',
            'district' => 'NTL',
            'ward' => 'MD',
            'address_line' => '123',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'bank_qr',
            'request_id' => 'req-003',
            'provider_order_id' => '260911120000000003',
            'amount' => 250000,
            'status' => 'paid',
            'paid_at' => now(),
            'transaction_id' => 'REF-PAID-001',
        ]);

        $response = $this->actingAs($user)->get(route('payments.bank', $order));
        $response->assertOk();
        $response->assertSee('THANH TOÁN THÀNH CÔNG');
        $response->assertSee('REF-PAID-001');
    }

    public function test_purchases_page_displays_bank_qr_badges_and_action(): void
    {
        $user = $this->customer();
        $order = Order::create([
            'user_id' => $user->id,
            'number' => 'ORD202609110004',
            'subtotal' => 250000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 250000,
            'payment_method' => 'bank_qr',
            'payment_status' => 'pending',
            'status' => 'pending_payment',
            'recipient_name' => 'Nguyễn An',
            'recipient_phone' => '0901234567',
            'recipient_email' => 'an@example.com',
            'province' => 'HN',
            'district' => 'NTL',
            'ward' => 'MD',
            'address_line' => '123',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'bank_qr',
            'request_id' => 'req-004',
            'provider_order_id' => '260911120000000004',
            'amount' => 250000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('purchases'));
        $response->assertOk();
        $response->assertSee('BANK QR');
        $response->assertSee('Chờ thanh toán');
        $response->assertSee('TIẾP TỤC THANH TOÁN →');
        $response->assertDontSee('LAB: GIẢ LẬP MOMO THÀNH CÔNG');
    }
}
