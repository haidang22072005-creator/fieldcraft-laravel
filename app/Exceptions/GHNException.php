<?php

namespace App\Exceptions;

use RuntimeException;

class GHNException extends RuntimeException
{
    public function __construct(string $message = 'GHN request failed.', public readonly ?int $status = null)
    {
        parent::__construct($message, $status ?? 0);
    }
}
