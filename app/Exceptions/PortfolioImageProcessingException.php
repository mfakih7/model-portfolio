<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * User-facing image processing error (shown in the admin panel).
 */
class PortfolioImageProcessingException extends RuntimeException
{
    public function __construct(
        string $message,
        ?Throwable $previous = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message, 0, $previous);
    }
}
