<?php

declare(strict_types=1);

namespace Salehye\Subscription\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Salehye\Subscription\Facades\Subscription as SubscriptionFacade;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Subscription;

trait HasSubscriptions
{
    /**
     * Get all subscriptions for this subscriber.
     *
     * @return MorphMany<Subscription>
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriber');
    }

    /**
     * Get the active primary subscription.
     */
    public function activeSubscription(): ?Subscription
    {
        return SubscriptionFacade::getActiveSubscription($this);
    }

    /**
     * Check if the subscriber has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return SubscriptionFacade::hasActiveSubscription($this);
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribeTo(Plan $plan, ?int $trialDays = null, bool $autoRenew = true, array $metadata = []): Subscription
    {
        return SubscriptionFacade::subscribe($this, $plan, null, $trialDays, $autoRenew, $metadata);
    }

    /**
     * Check if a feature is accessible.
     */
    public function canAccessFeature(string $featureSlug): bool
    {
        $subscription = $this->activeSubscription();

        if ($subscription === null) {
            return false;
        }

        return app(\Salehye\Subscription\Services\FeatureGuard::class)->can($subscription, $featureSlug);
    }

    /**
     * Consume a feature.
     */
    public function consumeFeature(string $featureSlug, int $units = 1): void
    {
        $subscription = $this->activeSubscription();

        if ($subscription === null) {
            throw new \RuntimeException('No active subscription found.');
        }

        app(\Salehye\Subscription\Services\FeatureGuard::class)->consume($subscription, $featureSlug, $units);
    }

    /**
     * Get remaining units for a feature.
     */
    public function remainingFeature(string $featureSlug): ?int
    {
        $subscription = $this->activeSubscription();

        if ($subscription === null) {
            return 0;
        }

        return app(\Salehye\Subscription\Services\FeatureGuard::class)->remaining($subscription, $featureSlug);
    }

    /**
     * Cancel the active subscription.
     */
    public function cancelSubscription(bool $immediately = false): ?Subscription
    {
        $subscription = $this->activeSubscription();

        if ($subscription === null) {
            return null;
        }

        return SubscriptionFacade::cancel($subscription, $immediately);
    }

    /**
     * Get the tenant ID for multi-tenancy support.
     */
    public function getTenantId(): ?string
    {
        return null; // Override in your model to return a tenant ID
    }
}
