<?php

declare(strict_types=1);

namespace Salehye\Subscription\Resolvers;

use Salehye\Subscription\Contracts\FeatureResolver;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Subscription;

/**
 * HierarchicalFeatureResolver extends DefaultFeatureResolver with support
 * for tenant-level feature overrides. If a tenant has a custom feature
 * value defined, it takes precedence over the plan's default.
 */
class HierarchicalFeatureResolver extends DefaultFeatureResolver implements FeatureResolver
{
    /**
     * Optional tenant-level feature overrides.
     *
     * @var array<string, array<string, string>>
     */
    protected array $tenantOverrides = [];

    /**
     * Set tenant-level feature overrides.
     *
     * @param  array<string, string>  $overrides  Feature slug => value
     */
    public function setTenantOverrides(string $tenantId, array $overrides): void
    {
        $this->tenantOverrides[$tenantId] = $overrides;
    }

    public function resolve(Subscription $subscription, Feature $feature): string
    {
        // Check for tenant-level override first
        if ($subscription->tenant_id !== null) {
            $override = $this->tenantOverrides[$subscription->tenant_id][$feature->slug] ?? null;

            if ($override !== null) {
                return $override;
            }
        }

        // Fall back to default resolution
        return parent::resolve($subscription, $feature);
    }
}
