<?php

namespace App\Services\Shipping;

class GrabExpressShippingProvider extends DisconnectedShippingProvider
{
    public function name(): string { return 'grabexpress'; }
}
