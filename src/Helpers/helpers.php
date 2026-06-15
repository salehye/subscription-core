<?php

declare(strict_types=1);

if (!function_exists('subscription')) {

    /**
     * Get the SubscriptionManager instance.
     *
     * @return \Salehye\Subscription\Contracts\SubscriptionManager
     */
    function subscription(): \Salehye\Subscription\Contracts\SubscriptionManager
    {
        return app(\Salehye\Subscription\Contracts\SubscriptionManager::class);
    }
}

if (!function_exists('featureGuard')) {

    /**
     * Get the FeatureGuard instance.
     *
     * @return \Salehye\Subscription\Services\FeatureGuard
     */
    function featureGuard(): \Salehye\Subscription\Services\FeatureGuard
    {
        return app(\Salehye\Subscription\Services\FeatureGuard::class);
    }
}
