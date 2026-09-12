<?php

namespace App\Contracts;

interface ShippingProvider
{
    public function name(): string;
    public function connected(): bool;
}
