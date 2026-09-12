<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;

abstract class DisconnectedShippingProvider implements ShippingProvider
{
    public function connected(): bool { return false; }
}
