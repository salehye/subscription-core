<?php

declare(strict_types=1);

namespace Salehye\Subscription\Exceptions;

use Exception;
use Throwable;

class FeatureLimitExceededException extends Exception
{
    public function __construct(
        string $featureSlug = '',
        int $limit = 0,
        int $current = 0,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        if (empty($message)) {
            $message = sprintf(
                'Feature limit exceeded for "%s". Limit: %d, Current usage: %d.',
                $featureSlug,
                $limit,
                $current,
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
