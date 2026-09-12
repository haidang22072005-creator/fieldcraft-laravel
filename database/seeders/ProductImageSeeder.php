<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    private const IMAGES = [
        'sample-1' => [
            'Trắng / Neon' => 'images/products/f50-white-neon.png',
            'Đen / Vàng' => 'images/products/f50-black-gold.png',
        ],
        'sample-2' => [
            'Đỏ rực' => 'images/products/mercurial-red.png',
            'Xanh cobalt' => 'images/products/mercurial-cobalt.png',
        ],
        'sample-3' => [
            'Hồng sân nhà' => 'images/products/messi-home-pink.png',
            'Đen sân khách' => 'images/products/messi-away-black.png',
        ],
        'sample-4' => [
            'Trắng / Đen' => 'images/products/ball-white-black.png',
            'Vàng / Đen' => 'images/products/ball-yellow-black.png',
        ],
    ];

    public function run(): void
    {
        foreach (self::IMAGES as $slug => $colors) {
            $product = Product::query()->where('slug', $slug)->first();

            if (! $product) {
                $this->command?->warn("Skipped missing product: {$slug}");
                continue;
            }

            $position = (int) $product->images()->max('position') + 1;
            foreach ($colors as $color => $path) {
                $image = $product->images()->where('color', $color)->first();
                if ($image) {
                    if ($image->path !== $path) {
                        $image->update(['path' => $path]);
                    }
                    continue;
                }

                $product->images()->create([
                    'color' => $color,
                    'path' => $path,
                    'position' => $position++,
                ]);
            }

            $this->command?->info("{$slug}: {$product->images()->count()} images");
        }
    }
}
