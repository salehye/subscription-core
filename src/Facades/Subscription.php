<?php

declare(strict_types=1);

namespace Salehye\Subscription\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Salehye\Subscription\Models\Subscription subscribe(\Salehye\Subscription\Contracts\HasSubscriptions $subscriber, \Salehye\Subscription\Models\Plan $plan, ?string $tenantId = null, ?int $trialDays = null, bool $autoRenew = true, array $metadata = [])
 * @method static \Salehye\Subscription\Models\Subscription cancel(\Salehye\Subscription\Models\Subscription $subscription, bool $immediately = false)
 * @method static \Salehye\Subscription\Models\Subscription renew(\Salehye\Subscription\Models\Subscription $subscription)
 * @method static \Salehye\Subscription\Models\Subscription switchPlan(\Salehye\Subscription\Models\Subscription $subscription, \Salehye\Subscription\Models\Plan $newPlan, bool $prorate = true)
 * @method static \Salehye\Subscription\Models\Subscription attachAddon(\Salehye\Subscription\Models\Subscription $parentSubscription, \Salehye\Subscription\Models\Plan $addonPlan, ?string $tenantId = null, array $metadata = [])
 * @method static \Salehye\Subscription\Models\Subscription pause(\Salehye\Subscription\Models\Subscription $subscription)
 * @method static \Salehye\Subscription\Models\Subscription resume(\Salehye\Subscription\Models\Subscription $subscription)
 * @method static \Salehye\Subscription\Models\Subscription|null getActiveSubscription(\Salehye\Subscription\Contracts\HasSubscriptions $subscriber)
 * @method static bool hasActiveSubscription(\Salehye\Subscription\Contracts\HasSubscriptions $subscriber)
 *
 * @see \Salehye\Subscription\Contracts\SubscriptionManager
 */
class Subscription extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Salehye\Subscription\Contracts\SubscriptionManager::class;
    }
}
