<?php

declare(strict_types=1);

namespace Salehye\Subscription\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Salehye\Subscription\Enums\BillingCycle;
use Salehye\Subscription\Enums\SubscriptionStatus;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Subscription;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $plan = Plan::factory()->create();
        $billingCycle = $plan->getBillingCycleEnum();
        $now = Carbon::now();

        return [
            'subscriber_type' => 'user',
            'subscriber_id' => (string) fake()->numberBetween(1, 1000),
            'plan_id' => $plan->id,
            'type' => 'primary',
            'parent_subscription_id' => null,
            'starts_at' => $now,
            'ends_at' => $billingCycle !== BillingCycle::Lifetime
                ? $now->copy()->addDays($billingCycle->days())
                : null,
            'trial_ends_at' => null,
            'status' => SubscriptionStatus::Active->value,
            'auto_renew' => true,
        ];
    }

    public function canceled(): static
    {
        return $this->state(fn() => [
            'status' => SubscriptionStatus::Canceled->value,
            'canceled_at' => Carbon::now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status' => SubscriptionStatus::Expired->value,
            'ends_at' => Carbon::now()->subDay(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn() => [
            'status' => SubscriptionStatus::Paused->value,
        ]);
    }

    public function addon(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'addon',
            'parent_subscription_id' => $attributes['parent_subscription_id']
                ?? Subscription::factory()->create()->id,
        ]);
    }

    public function forPlan(Plan $plan): static
    {
        return $this->state(fn() => ['plan_id' => $plan->id]);
    }
}
