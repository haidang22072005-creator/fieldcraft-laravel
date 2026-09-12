<?php

namespace App\Services;

use App\Services\Shipping\GrabBikeShippingProvider;
use App\Services\Shipping\GrabExpressShippingProvider;
use App\Services\Shipping\GhnShippingProvider;
use App\Services\Shipping\SpxShippingProvider;

class ShippingProviderRegistry
{
    public function providers(): array
    {
        $providers = [
            app(GhnShippingProvider::class),
            app(SpxShippingProvider::class),
            app(GrabExpressShippingProvider::class),
            app(GrabBikeShippingProvider::class),
        ];
        return collect($providers)->map(fn ($provider) => ['name' => $provider->name(), 'connected' => $provider->connected(), 'capabilities' => $provider->name() === 'ghn' ? ['booking', 'detail', 'cancel'] : []])->all();
    }
}
