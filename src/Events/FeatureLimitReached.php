<?php

declare(strict_types=1);

namespace Salehye\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;

class FeatureLimitReached
{
    use Dispatchable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly Feature $feature,
        public readonly int $limit,
        public readonly int $currentUsage,
    ) {
    }
}
