<?php

declare(strict_types=1);

namespace Salehye\Subscription\Resolvers;

use Salehye\Subscription\Contracts\FeatureResolver;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;

class DefaultFeatureResolver implements FeatureResolver
{
    /**
     * The cached feature values for the current request.
     *
     * @var array<string, string>
     */
    protected array $resolved = [];

    public function resolve(Subscription $subscription, Feature $feature): string
    {
        $cacheKey = $this->cacheKey($subscription, $feature);

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        // Get value from primary subscription's plan
        $primaryValue = $subscription->plan->getFeatureValue($feature->slug);

        // If it's unlimited or has no addons, return immediately
        if ($primaryValue === config('subscription.unlimited_value')) {
            $this->resolved[$cacheKey] = $primaryValue;

            return $primaryValue;
        }

        // Aggregate values from add-ons
        $totalValue = $primaryValue;

        foreach ($subscription->addons as $addon) {
            $addonValue = $addon->plan->getFeatureValue($feature->slug);

            if ($addonValue === config('subscription.unlimited_value')) {
                $this->resolved[$cacheKey] = $addonValue;

                return $addonValue;
            }

            if (is_numeric($addonValue) && is_numeric($totalValue)) {
                $totalValue = (string) ((int) $totalValue + (int) $addonValue);
            }
        }

        $this->resolved[$cacheKey] = $totalValue ?? config('subscription.unlimited_value');

        return $this->resolved[$cacheKey];
    }

    public function canAccess(Subscription $subscription, Feature $feature): bool
    {
        $value = $this->resolve($subscription, $feature);

        if ($value === config('subscription.unlimited_value')) {
            return true;
        }

        // For toggle features, check if value is "true" or "1"
        if ($feature->type === 'toggle') {
            return in_array(strtolower($value), ['true', '1', 'yes'], true);
        }

        // For non-toggle features, having any value means access
        return $value !== null && $value !== '' && $value !== '0';
    }

    public function getLimit(Subscription $subscription, Feature $feature): ?int
    {
        $value = $this->resolve($subscription, $feature);

        if ($value === config('subscription.unlimited_value')) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    public function isUnlimited(Subscription $subscription, Feature $feature): bool
    {
        return $this->resolve($subscription, $feature) === config('subscription.unlimited_value');
    }

    public function clearCache(): void
    {
        $this->resolved = [];
    }

    protected function cacheKey(Subscription $subscription, Feature $feature): string
    {
        return sprintf('%d_%d', $subscription->id, $feature->id);
    }
}
