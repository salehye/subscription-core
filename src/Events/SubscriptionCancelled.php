<?php

declare(strict_types=1);

namespace Salehye\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Salehye\Subscription\Models\Subscription;

class SubscriptionCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly bool $immediately = false,
    ) {
    }
}
