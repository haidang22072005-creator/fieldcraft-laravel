<?php

namespace App\Services\Shipping;

class GrabBikeShippingProvider extends DisconnectedShippingProvider
{
    public function name(): string { return 'grabbike'; }
}
