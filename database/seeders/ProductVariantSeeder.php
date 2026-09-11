<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Variant definitions aligned with the catalog definitions in DatabaseSeeder.
     * Existing rows are deliberately never updated or deleted.
     */
    private const DEFINITIONS = [
        'sample-1' => [
            'price' => 4890000,
            'prefix' => 'FC-001',
            'colors' => [
                'Trắng / Neon' => 'W',
                'Đen / Vàng' => 'B',
            ],
            'sizes' => ['38', '39', '40', '41', '42', '43', '44'],
        ],
        'sample-2' => [
            'price' => 4290000,
            'prefix' => 'FC-002',
            'colors' => [
                'Đỏ rực' => 'R',
                'Xanh cobalt' => 'C',
            ],
            'sizes' => ['38', '39', '40', '41', '42', '43', '44'],
        ],
        'sample-3' => [
            'price' => 1890000,
            'prefix' => 'FC-003',
            'colors' => [
                'Hồng sân nhà' => 'HS',
                'Đen sân khách' => 'B',
            ],
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
        ],
        'sample-4' => [
            'price' => 1090000,
            'prefix' => 'FC-004',
            'colors' => [
                'Trắng / Đen' => 'WD',
                'Vàng / Đen' => 'Y',
            ],
            'sizes' => ['4', '5'],
        ],
    ];

    public function run(): void
    {
        $added = 0;

        foreach (self::DEFINITIONS as $slug => $definition) {
            $product = Product::query()->where('slug', $slug)->first();

            if (! $product) {
                $this->command?->warn("Skipped missing product: {$slug}");
                continue;
            }

            foreach ($definition['colors'] as $color => $colorCode) {
                foreach ($definition['sizes'] as $size) {
                    $alreadyExists = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->where('color', $color)
                        ->where('size', $size)
                        ->exists();

                    if ($alreadyExists) {
                        continue;
                    }

                    $sku = $definition['prefix'].'-'.$colorCode.'-'.$size;
                    if (ProductVariant::query()->where('sku', $sku)->exists()) {
                        $this->command?->warn("Skipped occupied SKU: {$sku}");
                        continue;
                    }

                    ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'color' => $color,
                        'size' => $size,
                        'price' => $definition['price'],
                        'stock' => 20,
                    ]);
                    $added++;
                }
            }

            $this->command?->info("{$slug}: {$product->variants()->count()} variants");
        }

        $this->command?->info("Added {$added} missing product variants.");
    }
}
