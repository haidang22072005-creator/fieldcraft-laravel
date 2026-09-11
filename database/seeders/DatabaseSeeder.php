<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(['email' => 'admin@fieldcraft.vn'], [
            'name' => 'Fieldcraft Admin', 'password' => 'Admin@12345', 'role' => 'super-admin',
        ]);
        Coupon::query()->updateOrCreate(['code' => 'MESSI10'], ['type' => 'percent', 'value' => 10, 'minimum_order_value' => 1000000, 'usage_limit' => 500, 'is_active' => true]);
        $products = [
            [
                'name' => 'Adidas F50 Elite FG', 'brand' => 'adidas', 'category' => 'Giày đinh',
                'image' => 'https://images.unsplash.com/photo-1511886929837-354d827aae26?auto=format&fit=crop&w=900&q=85',
                'variants' => $this->variants('FC-001', 4890000, [
                    'Trắng / Neon' => ['W', ['40']],
                    'Đen / Vàng' => ['B', ['38', '39', '40', '41', '42', '43', '44']],
                ], 'FC-001-40'),
            ],
            [
                'name' => 'Nike Mercurial Vapor 16', 'brand' => 'nike', 'category' => 'Giày đinh',
                'image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=85',
                'variants' => $this->variants('FC-002', 4290000, [
                    'Đỏ rực' => ['R', ['40']],
                    'Xanh cobalt' => ['C', ['38', '39', '40', '41', '42', '43', '44']],
                ], 'FC-002-40'),
            ],
            [
                'name' => 'Áo Messi Inter Miami 25/26', 'brand' => 'adidas', 'category' => 'Áo đấu',
                'image' => 'https://images.unsplash.com/photo-1579952363873-27d3bfad9c0d?auto=format&fit=crop&w=900&q=85',
                'variants' => $this->variants('FC-003', 1890000, [
                    'Hồng' => ['H', ['40']],
                    'Hồng sân nhà' => ['HS', ['S', 'M', 'L', 'XL', 'XXL']],
                    'Đen sân khách' => ['B', ['S', 'M', 'L', 'XL', 'XXL']],
                ], 'FC-003-40'),
            ],
            [
                'name' => 'Bóng Adidas League', 'brand' => 'adidas', 'category' => 'Bóng đá',
                'image' => 'https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=900&q=85',
                'variants' => $this->variants('FC-004', 1090000, [
                    'Trắng' => ['W', ['40']],
                    'Trắng / Đen' => ['WD', ['4', '5']],
                    'Vàng / Đen' => ['Y', ['5']],
                ], 'FC-004-40'),
            ],
        ];
        foreach ($products as $i => $item) {
            $product = Product::query()->updateOrCreate(['slug' => 'sample-'.($i+1)], ['name'=>$item['name'],'brand'=>$item['brand'],'category'=>$item['category'],'description'=>'Sản phẩm thể thao chính hãng Fieldcraft.','is_active'=>true]);
            $product->images()->updateOrCreate(['position'=>0], ['path'=>$item['image']]);
            foreach ($item['variants'] as $variant) {
                $product->variants()->updateOrCreate(
                    ['sku' => $variant['sku']],
                    ['color' => $variant['color'], 'size' => $variant['size'], 'price' => $variant['price'], 'stock' => $variant['stock']],
                );
            }
        }
    }

    private function variants(string $prefix, int $price, array $colors, string $legacySku): array
    {
        $variants = [];
        $legacyUsed = false;
        foreach ($colors as $color => [$code, $sizes]) {
            foreach ($sizes as $size) {
                $sku = $prefix.'-'.$code.'-'.$size;
                if (! $legacyUsed) {
                    $sku = $legacySku;
                    $legacyUsed = true;
                }
                $variants[] = ['sku' => $sku, 'color' => $color, 'size' => $size, 'price' => $price, 'stock' => 20];
            }
        }
        return $variants;
    }
}
