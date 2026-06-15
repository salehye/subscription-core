<?php

declare(strict_types=1);

namespace Salehye\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Subscription;

class PlanChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly Plan $oldPlan,
        public readonly Plan $newPlan,
        public readonly bool $prorated = true,
    ) {
    }
}
