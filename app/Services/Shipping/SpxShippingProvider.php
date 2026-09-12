<?php

namespace App\Services\Shipping;

class SpxShippingProvider extends DisconnectedShippingProvider
{
    public function name(): string { return 'spx'; }
}
