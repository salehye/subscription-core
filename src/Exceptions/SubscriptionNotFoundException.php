<?php

declare(strict_types=1);

namespace Salehye\Subscription\Exceptions;

use Exception;
use Throwable;

class SubscriptionNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Subscription not found.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
