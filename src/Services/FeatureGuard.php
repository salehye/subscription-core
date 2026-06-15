<?php

declare(strict_types=1);

namespace Salehye\Subscription\Services;

use Salehye\Subscription\Contracts\FeatureResolver;
use Salehye\Subscription\Enums\FeatureType;
use Salehye\Subscription\Exceptions\FeatureLimitExceededException;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;

class FeatureGuard
{
    public function __construct(
        protected FeatureResolver $featureResolver,
        protected UsageTracker $usageTracker,
    ) {
    }

    /**
     * Check if a subscription can access a feature.
     */
    public function can(Subscription $subscription, string $featureSlug): bool
    {
        $feature = $this->resolveFeature($featureSlug);

        if ($feature === null) {
            return false;
        }

        return $this->featureResolver->canAccess($subscription, $feature);
    }

    /**
     * Consume units of a consumable feature.
     *
     * @throws FeatureLimitExceededException
     */
    public function consume(Subscription $subscription, string $featureSlug, int $units = 1): void
    {
        $feature = $this->resolveFeature($featureSlug);

        if ($feature === null || $feature->type !== FeatureType::Consumable->value) {
            throw new \InvalidArgumentException("Feature '{$featureSlug}' is not consumable.");
        }

        $limit = $this->featureResolver->getLimit($subscription, $feature);

        // Unlimited consumable
        if ($limit === null) {
            $this->usageTracker->track($subscription, $feature, $units);

            return;
        }

        $currentUsage = $this->usageTracker->getUsage($subscription, $feature);
        $newUsage = $currentUsage + $units;

        if ($newUsage > $limit) {
            throw new FeatureLimitExceededException($featureSlug, $limit, $currentUsage);
        }

        $this->usageTracker->track($subscription, $feature, $units);
    }

    /**
     * Get the remaining units for a consumable/limit feature.
     */
    public function remaining(Subscription $subscription, string $featureSlug): ?int
    {
        $feature = $this->resolveFeature($featureSlug);

        if ($feature === null) {
            return 0;
        }

        $limit = $this->featureResolver->getLimit($subscription, $feature);

        // Unlimited
        if ($limit === null) {
            return PHP_INT_MAX;
        }

        $currentUsage = $this->usageTracker->getUsage($subscription, $feature);

        return max(0, $limit - $currentUsage);
    }

    /**
     * Get the limit for a feature.
     */
    public function limit(Subscription $subscription, string $featureSlug): ?int
    {
        $feature = $this->resolveFeature($featureSlug);

        if ($feature === null) {
            return 0;
        }

        return $this->featureResolver->getLimit($subscription, $feature);
    }

    /**
     * Get the current usage for a consumable feature.
     */
    public function usage(Subscription $subscription, string $featureSlug): int
    {
        $feature = $this->resolveFeature($featureSlug);

        if ($feature === null) {
            return 0;
        }

        return $this->usageTracker->getUsage($subscription, $feature);
    }

    protected function resolveFeature(string $slug): ?Feature
    {
        /** @var Feature|null */
        return Feature::query()->where('slug', $slug)->first();
    }
}
