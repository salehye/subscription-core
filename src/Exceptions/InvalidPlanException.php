<?php

declare(strict_types=1);

namespace Salehye\Subscription\Exceptions;

use Exception;
use Throwable;

class InvalidPlanException extends Exception
{
    public function __construct(
        string $message = 'The specified plan is invalid or inactive.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
