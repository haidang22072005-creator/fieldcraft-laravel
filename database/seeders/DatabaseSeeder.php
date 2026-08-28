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
            ['name'=>'Adidas F50 Elite FG','brand'=>'adidas','category'=>'Giày đinh','image'=>'https://images.unsplash.com/photo-1511886929837-354d827aae26?auto=format&fit=crop&w=900&q=85','price'=>4890000,'color'=>'Trắng / Neon'],
            ['name'=>'Nike Mercurial Vapor 16','brand'=>'nike','category'=>'Giày đinh','image'=>'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=85','price'=>4290000,'color'=>'Đỏ rực'],
            ['name'=>'Áo Messi Inter Miami 25/26','brand'=>'adidas','category'=>'Áo đấu','image'=>'https://images.unsplash.com/photo-1579952363873-27d3bfad9c0d?auto=format&fit=crop&w=900&q=85','price'=>1890000,'color'=>'Hồng'],
            ['name'=>'Bóng Adidas League','brand'=>'adidas','category'=>'Bóng đá','image'=>'https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=900&q=85','price'=>1090000,'color'=>'Trắng'],
        ];
        foreach ($products as $i => $item) {
            $product = Product::query()->updateOrCreate(['slug' => 'sample-'.($i+1)], ['name'=>$item['name'],'brand'=>$item['brand'],'category'=>$item['category'],'description'=>'Sản phẩm thể thao chính hãng Fieldcraft.','is_active'=>true]);
            $product->images()->updateOrCreate(['position'=>0], ['path'=>$item['image']]);
            $product->variants()->updateOrCreate(['sku'=>'FC-'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT).'-40'], ['color'=>$item['color'],'size'=>'40','price'=>$item['price'],'stock'=>20]);
        }
    }
}
