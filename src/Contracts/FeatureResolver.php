<?php

declare(strict_types=1);

namespace Salehye\Subscription\Contracts;

use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;

interface FeatureResolver
{
    /**
     * Resolve the value of a feature for a given subscription.
     * This method should aggregate feature values from the primary
     * subscription and any add-ons.
     *
     * @return string The resolved feature value (e.g., "true", "100", "unlimited")
     */
    public function resolve(Subscription $subscription, Feature $feature): string;

    /**
     * Check if a feature is available for a subscription (toggle type).
     */
    public function canAccess(Subscription $subscription, Feature $feature): bool;

    /**
     * Get the numeric limit for a feature (consumable/limit type).
     * Returns null for "unlimited" values.
     */
    public function getLimit(Subscription $subscription, Feature $feature): ?int;

    /**
     * Check if a feature has unlimited value.
     */
    public function isUnlimited(Subscription $subscription, Feature $feature): bool;
}
