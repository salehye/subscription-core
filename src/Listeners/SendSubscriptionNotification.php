<?php

declare(strict_types=1);

namespace Salehye\Subscription\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Salehye\Subscription\Events\SubscriptionCancelled;
use Salehye\Subscription\Events\SubscriptionExpired;
use Salehye\Subscription\Events\SubscriptionStarted;

/**
 * Optional listener that logs subscription events.
 * In production, replace this with actual notification logic
 * (e.g., sending emails, SMS, or push notifications).
 */
class SendSubscriptionNotification implements ShouldQueue
{
    public function handleSubscriptionStarted(SubscriptionStarted $event): void
    {
        $subscription = $event->subscription;
        $subscriber = $subscription->subscriber;

        Log::info('Subscription started', [
            'subscriber_id' => $subscriber?->getKey(),
            'subscriber_type' => $subscription->subscriber_type,
            'plan' => $subscription->plan->slug,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function handleSubscriptionCancelled(SubscriptionCancelled $event): void
    {
        $subscription = $event->subscription;

        Log::info('Subscription cancelled', [
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan->slug,
            'immediately' => $event->immediately,
        ]);
    }

    public function handleSubscriptionExpired(SubscriptionExpired $event): void
    {
        $subscription = $event->subscription;

        Log::info('Subscription expired', [
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan->slug,
        ]);
    }

    public function subscribe(): array
    {
        return [
            SubscriptionStarted::class => 'handleSubscriptionStarted',
            SubscriptionCancelled::class => 'handleSubscriptionCancelled',
            SubscriptionExpired::class => 'handleSubscriptionExpired',
        ];
    }
}
