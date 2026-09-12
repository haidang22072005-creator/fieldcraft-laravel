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
        $prefix = $term.'%';
        return [
            'orders' => Order::with('user')->where(fn ($q) => $q->where('number', $term)->orWhere('number', 'like', $prefix)->orWhere('ghn_order_code', $term)->orWhere('ghn_order_code', 'like', $prefix)->orWhere('recipient_phone', 'like', $prefix)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $prefix)->orWhere('email', 'like', $prefix)->orWhere('phone', 'like', $prefix)))->orderByRaw('CASE WHEN number = ? OR ghn_order_code = ? THEN 0 ELSE 1 END', [$term, $term])->limit(10)->get(),
            'customers' => User::where('role', 'customer')->where(fn ($q) => $q->where('name', 'like', $prefix)->orWhere('email', 'like', $prefix)->orWhere('phone', 'like', $prefix))->limit(10)->get(),
            'products' => Product::with('variants')->where(fn ($q) => $q->where('name', 'like', $prefix)->orWhere('brand', 'like', $prefix)->orWhereHas('variants', fn ($v) => $v->where('sku', $term)->orWhere('sku', 'like', $prefix)))->orderByRaw('CASE WHEN name = ? OR brand = ? THEN 0 ELSE 1 END', [$term, $term])->limit(10)->get(),
            'teams' => TeamProfile::where(fn ($q) => $q->where('team_name', 'like', $prefix)->orWhere('captain', 'like', $prefix)->orWhere('contact', 'like', $prefix)->orWhere('phone', 'like', $prefix))->limit(10)->get(),
            'second_hand' => SecondHandListing::with('user')->where(fn ($q) => $q->where('product_name', 'like', $prefix)->orWhere('brand', 'like', $prefix)->orWhere('size', 'like', $prefix))->limit(10)->get(),
            'boot_passports' => BootPassport::with('user')->where(fn ($q) => $q->where('passport_code', $term)->orWhere('passport_code', 'like', $prefix)->orWhereHas('order', fn ($o) => $o->where('number', $term)->orWhere('number', 'like', $prefix)))->orderByRaw('CASE WHEN passport_code = ? THEN 0 ELSE 1 END', [$term])->limit(10)->get(),
        ];
    }
}
