<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Services\GHNService;

class GhnShippingProvider implements ShippingProvider
{
    public function __construct(private GHNService $service) {}
    public function name(): string { return 'ghn'; }
    public function connected(): bool { return $this->service->isConfigured(); }
}
