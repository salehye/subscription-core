<?php

declare(strict_types=1);

namespace Salehye\Subscription\Contracts;

use Salehye\Subscription\Enums\BillingCycle;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Subscription;

interface SubscriptionManager
{
    /**
     * Create a new subscription for a subscriber.
     */
    public function subscribe(
        HasSubscriptions $subscriber,
        Plan $plan,
        ?string $tenantId = null,
        ?int $trialDays = null,
        bool $autoRenew = true,
        array $metadata = [],
    ): Subscription;

    /**
     * Cancel an active subscription.
     */
    public function cancel(
        Subscription $subscription,
        bool $immediately = false,
    ): Subscription;

    /**
     * Renew a subscription.
     */
    public function renew(Subscription $subscription): Subscription;

    /**
     * Switch a subscription to a different plan.
     */
    public function switchPlan(
        Subscription $subscription,
        Plan $newPlan,
        bool $prorate = true,
    ): Subscription;

    /**
     * Add an add-on subscription to a primary subscription.
     */
    public function attachAddon(
        Subscription $parentSubscription,
        Plan $addonPlan,
        ?string $tenantId = null,
        array $metadata = [],
    ): Subscription;

    /**
     * Pause an active subscription.
     */
    public function pause(Subscription $subscription): Subscription;

    /**
     * Resume a paused subscription.
     */
    public function resume(Subscription $subscription): Subscription;

    /**
     * Get the active subscription for a subscriber.
     */
    public function getActiveSubscription(HasSubscriptions $subscriber): ?Subscription;

    /**
     * Check if a subscriber has an active subscription.
     */
    public function hasActiveSubscription(HasSubscriptions $subscriber): bool;
}
