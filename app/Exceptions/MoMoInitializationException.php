<?php

namespace App\Exceptions;

use RuntimeException;

class MoMoInitializationException extends RuntimeException
{
    public function __construct(
        public readonly ?int $resultCode,
        public readonly ?string $providerMessage,
    ) {
        parent::__construct('MoMo payment initialization failed.');
    }
}
