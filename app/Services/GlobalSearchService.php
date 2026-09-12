<?php

namespace App\Services;

use App\Models\BootPassport;
use App\Models\Order;
use App\Models\Product;
use App\Models\SecondHandListing;
use App\Models\TeamProfile;
use App\Models\User;

class GlobalSearchService
{
    public function search(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];
        $like = '%'.$term.'%';
        return [
            'orders' => Order::with('user')->where('number', 'like', $like)->orWhere('ghn_order_code', 'like', $like)->orWhereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like))->limit(10)->get(),
            'customers' => User::where('role', 'customer')->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like))->limit(10)->get(),
            'products' => Product::with('variants')->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('brand', 'like', $like)->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $like)))->limit(10)->get(),
            'teams' => TeamProfile::where('team_name', 'like', $like)->orWhere('captain', 'like', $like)->limit(10)->get(),
            'second_hand' => SecondHandListing::with('user')->where(fn ($q) => $q->where('product_name', 'like', $like)->orWhere('brand', 'like', $like)->orWhere('size', 'like', $like))->limit(10)->get(),
            'boot_passports' => BootPassport::with('user')->where('passport_code', 'like', $like)->orWhereHas('order', fn ($q) => $q->where('number', 'like', $like))->limit(10)->get(),
        ];
    }
}
