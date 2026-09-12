<?php

namespace App\Services\TwelveData\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when the TwelveData API responds with an error, either via a
 * non-2xx HTTP status or a 200 response whose payload reports an error.
 */
class TwelveDataException extends RuntimeException
{
    public function __construct(string $message, protected readonly int $statusCode = 502, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
