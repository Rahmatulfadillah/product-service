<?php

namespace App\Exceptions;

use RuntimeException;

class OrderBusinessException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 409
    ) {
        parent::__construct($message);
    }
}