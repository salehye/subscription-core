<?php

declare(strict_types=1);

namespace Salehye\Subscription\Services;

use Carbon\Carbon;
use Salehye\Subscription\Events\FeatureConsumed;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;
use Salehye\Subscription\Models\SubscriptionUsage;
use Salehye\Subscription\Repositories\EloquentSubscriptionRepository;

class UsageTracker
{
    /**
     * @var array<string, int> Cached usage counts
     */
    protected array $usageCache = [];

    public function __construct(
        protected EloquentSubscriptionRepository $subscriptionRepository,
    ) {
    }

    /**
     * Track usage of a feature for a subscription.
     */
    public function track(Subscription $subscription, Feature $feature, int $units = 1): SubscriptionUsage
    {
        $now = Carbon::now();
        $periodStart = $subscription->starts_at->copy();
        $periodEnd = $subscription->ends_at?->copy();

        $existing = $this->subscriptionRepository->getUsageForPeriod($subscription, $feature->slug);

        $newUsed = ($existing ? $existing->used : 0) + $units;

        $usage = $this->subscriptionRepository->createOrUpdateUsage(
            $subscription,
            $feature->id,
            $newUsed,
            $periodStart,
            $periodEnd,
        );

        // Update cache
        $cacheKey = $this->cacheKey($subscription, $feature);
        $this->usageCache[$cacheKey] = $newUsed;

        event(new FeatureConsumed($subscription, $feature, $units, $newUsed));

        return $usage;
    }

    /**
     * Get the current usage for a feature.
     */
    public function getUsage(Subscription $subscription, Feature $feature): int
    {
        $cacheKey = $this->cacheKey($subscription, $feature);

        if (isset($this->usageCache[$cacheKey])) {
            return $this->usageCache[$cacheKey];
        }

        $usage = $this->subscriptionRepository->getUsageForPeriod($subscription, $feature->slug);
        $value = $usage ? $usage->used : 0;

        $this->usageCache[$cacheKey] = $value;

        return $value;
    }

    /**
     * Reset usage for a subscription (e.g., on renewal).
     */
    public function reset(Subscription $subscription): void
    {
        $this->subscriptionRepository->resetUsage($subscription);

        // Clear cached entries for this subscription
        foreach ($this->usageCache as $key => $value) {
            if (str_starts_with($key, (string) $subscription->id)) {
                unset($this->usageCache[$key]);
            }
        }
    }

    /**
     * Clear all cached usage.
     */
    public function clearCache(): void
    {
        $this->usageCache = [];
    }

    protected function cacheKey(Subscription $subscription, Feature $feature): string
    {
        return sprintf('%d_%d', $subscription->id, $feature->id);
    }
}
