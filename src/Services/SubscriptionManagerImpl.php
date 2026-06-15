<?php

declare(strict_types=1);

namespace Salehye\Subscription\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Salehye\Subscription\Contracts\HasSubscriptions;
use Salehye\Subscription\Contracts\SubscriptionManager;
use Salehye\Subscription\Enums\BillingCycle;
use Salehye\Subscription\Enums\SubscriptionStatus;
use Salehye\Subscription\Events\PlanChanged;
use Salehye\Subscription\Events\SubscriptionCancelled;
use Salehye\Subscription\Events\SubscriptionExpired;
use Salehye\Subscription\Events\SubscriptionStarted;
use Salehye\Subscription\Exceptions\InvalidPlanException;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Subscription;
use Salehye\Subscription\Repositories\EloquentSubscriptionRepository;

class SubscriptionManagerImpl implements SubscriptionManager
{
    public function __construct(
        protected EloquentSubscriptionRepository $subscriptionRepository,
        protected UsageTracker $usageTracker,
    ) {
    }

    public function subscribe(
        HasSubscriptions $subscriber,
        Plan $plan,
        ?string $tenantId = null,
        ?int $trialDays = null,
        bool $autoRenew = true,
        array $metadata = [],
    ): Subscription {
        if (!$plan->is_active) {
            throw new InvalidPlanException("Plan '{$plan->slug}' is not active.");
        }

        $now = Carbon::now();
        $trialDays = $trialDays ?? $plan->trial_days;
        $trialEndsAt = $trialDays > 0 ? $now->copy()->addDays($trialDays) : null;

        $billingCycle = $plan->getBillingCycleEnum();
        $endsAt = $billingCycle !== BillingCycle::Lifetime
            ? $now->copy()->addDays($billingCycle->days())
            : null;

        return DB::transaction(function () use ($subscriber, $plan, $tenantId, $now, $trialEndsAt, $endsAt, $autoRenew, $metadata) {
            $subscription = $this->subscriptionRepository->create([
                'subscriber_type' => $subscriber->getMorphClass(),
                'subscriber_id' => (string) $subscriber->getKey(),
                'tenant_id' => $tenantId ?? $subscriber->getTenantId(),
                'plan_id' => $plan->id,
                'type' => 'primary',
                'parent_subscription_id' => null,
                'starts_at' => $now,
                'ends_at' => $endsAt,
                'trial_ends_at' => $trialEndsAt,
                'status' => SubscriptionStatus::Active->value,
                'canceled_at' => null,
                'auto_renew' => $autoRenew,
                'metadata' => $metadata,
            ]);

            event(new SubscriptionStarted($subscription));

            return $subscription;
        });
    }

    public function cancel(
        Subscription $subscription,
        bool $immediately = false,
    ): Subscription {
        return DB::transaction(function () use ($subscription, $immediately) {
            $now = Carbon::now();

            $data = [
                'status' => SubscriptionStatus::Canceled->value,
                'canceled_at' => $now,
            ];

            if ($immediately) {
                $data['ends_at'] = $now;
            }

            $subscription = $this->subscriptionRepository->update($subscription, $data);

            // Also cancel active add-ons
            foreach ($subscription->addons as $addon) {
                if ($addon->isActive()) {
                    $this->cancel($addon, $immediately);
                }
            }

            event(new SubscriptionCancelled($subscription, $immediately));

            return $subscription;
        });
    }

    public function renew(Subscription $subscription): Subscription
    {
        if (!$subscription->auto_renew) {
            throw new \RuntimeException('Subscription does not have auto-renew enabled.');
        }

        return DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;
            $billingCycle = $plan->getBillingCycleEnum();

            $now = Carbon::now();
            $newEndsAt = $billingCycle !== BillingCycle::Lifetime
                ? $now->copy()->addDays($billingCycle->days())
                : null;

            $subscription = $this->subscriptionRepository->update($subscription, [
                'status' => SubscriptionStatus::Active->value,
                'starts_at' => $now,
                'ends_at' => $newEndsAt,
                'canceled_at' => null,
            ]);

            // Reset usage for the new period
            $this->usageTracker->reset($subscription);

            event(new SubscriptionStarted($subscription));

            return $subscription;
        });
    }

    public function switchPlan(
        Subscription $subscription,
        Plan $newPlan,
        bool $prorate = true,
    ): Subscription {
        if (!$newPlan->is_active) {
            throw new InvalidPlanException("Plan '{$newPlan->slug}' is not active.");
        }

        return DB::transaction(function () use ($subscription, $newPlan, $prorate) {
            $oldPlan = $subscription->plan;

            $billingCycle = $newPlan->getBillingCycleEnum();
            $now = Carbon::now();
            $newEndsAt = $billingCycle !== BillingCycle::Lifetime
                ? $now->copy()->addDays($billingCycle->days())
                : null;

            $subscription = $this->subscriptionRepository->update($subscription, [
                'plan_id' => $newPlan->id,
                'ends_at' => $newEndsAt,
                'status' => SubscriptionStatus::Active->value,
                'canceled_at' => null,
            ]);

            // Reset usage when switching plans
            $this->usageTracker->reset($subscription);

            event(new PlanChanged($subscription, $oldPlan, $newPlan, $prorate));

            return $subscription;
        });
    }

    public function attachAddon(
        Subscription $parentSubscription,
        Plan $addonPlan,
        ?string $tenantId = null,
        array $metadata = [],
    ): Subscription {
        if (!$addonPlan->is_active) {
            throw new InvalidPlanException("Add-on plan '{$addonPlan->slug}' is not active.");
        }

        if (!$parentSubscription->isPrimary()) {
            throw new \RuntimeException('Add-ons can only be attached to primary subscriptions.');
        }

        $now = Carbon::now();
        $billingCycle = $addonPlan->getBillingCycleEnum();
        $endsAt = $billingCycle !== BillingCycle::Lifetime
            ? $now->copy()->addDays($billingCycle->days())
            : null;

        return DB::transaction(function () use ($parentSubscription, $addonPlan, $tenantId, $now, $endsAt, $metadata) {
            $addon = $this->subscriptionRepository->create([
                'subscriber_type' => $parentSubscription->subscriber_type,
                'subscriber_id' => $parentSubscription->subscriber_id,
                'tenant_id' => $tenantId ?? $parentSubscription->tenant_id,
                'plan_id' => $addonPlan->id,
                'type' => 'addon',
                'parent_subscription_id' => $parentSubscription->id,
                'starts_at' => $now,
                'ends_at' => $endsAt,
                'trial_ends_at' => null,
                'status' => SubscriptionStatus::Active->value,
                'canceled_at' => null,
                'auto_renew' => $parentSubscription->auto_renew,
                'metadata' => $metadata,
            ]);

            event(new SubscriptionStarted($addon));

            return $addon;
        });
    }

    public function pause(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            return $this->subscriptionRepository->update($subscription, [
                'status' => SubscriptionStatus::Paused->value,
            ]);
        });
    }

    public function resume(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            return $this->subscriptionRepository->update($subscription, [
                'status' => SubscriptionStatus::Active->value,
            ]);
        });
    }

    public function getActiveSubscription(HasSubscriptions $subscriber): ?Subscription
    {
        return $this->subscriptionRepository->findActiveForSubscriber($subscriber);
    }

    public function hasActiveSubscription(HasSubscriptions $subscriber): bool
    {
        return $this->getActiveSubscription($subscriber) !== null;
    }
}
