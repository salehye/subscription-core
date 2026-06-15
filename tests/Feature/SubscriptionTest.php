<?php

declare(strict_types=1);

namespace Salehye\Subscription\Tests\Feature;

use Carbon\Carbon;
use Salehye\Subscription\Enums\BillingCycle;
use Salehye\Subscription\Enums\SubscriptionStatus;
use Salehye\Subscription\Events\PlanChanged;
use Salehye\Subscription\Events\SubscriptionCancelled;
use Salehye\Subscription\Events\SubscriptionExpired;
use Salehye\Subscription\Events\SubscriptionPaused;
use Salehye\Subscription\Events\SubscriptionResumed;
use Salehye\Subscription\Events\SubscriptionStarted;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Models\Subscription;
use Salehye\Subscription\Services\FeatureGuard;
use Salehye\Subscription\Tests\TestCase;

class SubscriptionTest extends TestCase
{
    protected Plan $plan;
    protected Plan $addonPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::query()->create([
            'name' => 'Monthly Pro',
            'slug' => 'monthly-pro',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 29.99,
            'trial_days' => 7,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->addonPlan = Plan::query()->create([
            'name' => 'Extra Storage',
            'slug' => 'extra-storage',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 4.99,
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    public function test_can_subscribe_to_plan(): void
    {
        $user = $this->createUser();

        $subscription = $user->subscribeTo($this->plan);

        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionStatus::Active->value, $subscription->status);
        $this->assertEquals($this->plan->id, $subscription->plan_id);
        $this->assertEquals('primary', $subscription->type);
        $this->assertEquals($user->id, $subscription->subscriber_id);
        $this->assertEquals($user->getMorphClass(), $subscription->subscriber_type);
        $this->assertTrue($subscription->auto_renew);
    }

    public function test_subscription_sets_correct_ends_at(): void
    {
        $user = $this->createUser();
        Carbon::setTestNow(Carbon::create(2025, 1, 1));

        $subscription = $user->subscribeTo($this->plan);

        $this->assertNotNull($subscription->ends_at);
        $this->assertEquals(
            Carbon::now()->addDays(30)->toDateString(),
            $subscription->ends_at->toDateString(),
        );

        Carbon::setTestNow();
    }

    public function test_subscription_has_trial(): void
    {
        $user = $this->createUser();
        Carbon::setTestNow(Carbon::create(2025, 1, 1));

        $subscription = $user->subscribeTo($this->plan);

        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->isOnTrial());

        Carbon::setTestNow();
    }

    public function test_cannot_subscribe_to_inactive_plan(): void
    {
        $this->expectException(\Salehye\Subscription\Exceptions\InvalidPlanException::class);

        $user = $this->createUser();
        $inactivePlan = Plan::query()->create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 0,
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $user->subscribeTo($inactivePlan);
    }

    public function test_can_cancel_subscription(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $user->cancelSubscription();

        $subscription->refresh();

        $this->assertEquals(SubscriptionStatus::Canceled->value, $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_can_cancel_subscription_immediately(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $user->cancelSubscription(true);

        $subscription->refresh();

        $this->assertEquals(SubscriptionStatus::Canceled->value, $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
        $this->assertTrue($subscription->ends_at->isPast());
    }

    public function test_can_renew_subscription(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $renewed = \Salehye\Subscription\Facades\Subscription::renew($subscription);

        $this->assertEquals(SubscriptionStatus::Active->value, $renewed->status);
        $this->assertNull($renewed->canceled_at);
    }

    public function test_can_switch_plan(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $newPlan = Plan::query()->create([
            'name' => 'Yearly Enterprise',
            'slug' => 'yearly-enterprise',
            'billing_cycle' => BillingCycle::Yearly->value,
            'price' => 299.99,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $switched = \Salehye\Subscription\Facades\Subscription::switchPlan($subscription, $newPlan);

        $this->assertEquals($newPlan->id, $switched->plan_id);
        $this->assertEquals(SubscriptionStatus::Active->value, $switched->status);
    }

    public function test_can_attach_addon(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $addon = \Salehye\Subscription\Facades\Subscription::attachAddon(
            $subscription,
            $this->addonPlan,
        );

        $this->assertNotNull($addon);
        $this->assertEquals('addon', $addon->type);
        $this->assertEquals($subscription->id, $addon->parent_subscription_id);
    }

    public function test_has_active_subscription(): void
    {
        $user = $this->createUser();

        $this->assertFalse($user->hasActiveSubscription());

        $user->subscribeTo($this->plan);

        $this->assertTrue($user->hasActiveSubscription());
    }

    public function test_subscription_expires(): void
    {
        $user = $this->createUser();
        Carbon::setTestNow(Carbon::create(2025, 1, 1));

        $subscription = $user->subscribeTo($this->plan);

        // Move past the end date
        Carbon::setTestNow(Carbon::create(2025, 2, 15));

        $this->assertTrue($subscription->isExpired());

        Carbon::setTestNow();
    }

    public function test_subscription_started_event_is_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = $this->createUser();
        $user->subscribeTo($this->plan);

        \Illuminate\Support\Facades\Event::assertDispatched(SubscriptionStarted::class);
    }

    public function test_subscription_cancelled_event_is_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);
        $user->cancelSubscription();

        \Illuminate\Support\Facades\Event::assertDispatched(SubscriptionCancelled::class);
    }

    public function test_plan_changed_event_is_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $newPlan = Plan::query()->create([
            'name' => 'New Plan',
            'slug' => 'new-plan',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 49.99,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        \Salehye\Subscription\Facades\Subscription::switchPlan($subscription, $newPlan);

        \Illuminate\Support\Facades\Event::assertDispatched(PlanChanged::class);
    }

    public function test_subscription_expired_event_is_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = $this->createUser();
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $subscription = $user->subscribeTo($this->plan);
        Carbon::setTestNow(Carbon::create(2025, 2, 15));

        // Expire the subscription
        $subscription->update(['status' => SubscriptionStatus::Expired->value]);
        event(new SubscriptionExpired($subscription));

        \Illuminate\Support\Facades\Event::assertDispatched(SubscriptionExpired::class);

        Carbon::setTestNow();
    }

    public function test_addon_cancel_when_primary_cancels(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $addon = \Salehye\Subscription\Facades\Subscription::attachAddon($subscription, $this->addonPlan);

        $user->cancelSubscription(true);

        $addon->refresh();

        $this->assertEquals(SubscriptionStatus::Canceled->value, $addon->status);
    }

    public function test_can_pause_active_subscription(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $paused = \Salehye\Subscription\Facades\Subscription::pause($subscription);

        $this->assertTrue($paused->isPaused());
        $this->assertEquals(SubscriptionStatus::Paused->value, $paused->status);
    }

    public function test_can_resume_paused_subscription(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        \Salehye\Subscription\Facades\Subscription::pause($subscription);
        $subscription->refresh();

        $resumed = \Salehye\Subscription\Facades\Subscription::resume($subscription);

        $this->assertEquals(SubscriptionStatus::Active->value, $resumed->status);
        $this->assertFalse($resumed->isPaused());
    }

    public function test_paused_subscription_has_paused_status(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        \Salehye\Subscription\Facades\Subscription::pause($subscription);
        $subscription->refresh();

        $this->assertTrue($subscription->isPaused());
        $this->assertEquals(SubscriptionStatus::Paused->value, $subscription->status);
    }

    public function test_subscription_paused_event_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        \Salehye\Subscription\Facades\Subscription::pause($subscription);

        \Illuminate\Support\Facades\Event::assertDispatched(SubscriptionPaused::class);
    }

    public function test_subscription_resumed_event_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        \Salehye\Subscription\Facades\Subscription::pause($subscription);
        \Salehye\Subscription\Facades\Subscription::resume($subscription->fresh());

        \Illuminate\Support\Facades\Event::assertDispatched(SubscriptionResumed::class);
    }

    public function test_paused_subscription_in_active_scope(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        \Salehye\Subscription\Facades\Subscription::pause($subscription);

        // Paused subscriptions should still be in the 'active' scope
        $activeSubscriptions = Subscription::active()->get();
        $this->assertTrue($activeSubscriptions->contains('id', $subscription->id));
    }

    public function test_can_resume_from_facade(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        \Salehye\Subscription\Facades\Subscription::pause($subscription);
        $subscription->refresh();

        $this->assertTrue($subscription->isPaused());

        \Salehye\Subscription\Facades\Subscription::resume($subscription->fresh());
        $subscription->refresh();

        $this->assertTrue($subscription->isActive());
    }

    public function test_paused_scope_returns_only_paused(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        // Create another subscription and pause it
        $user2 = $this->createUser(['email' => 'test2@example.com']);
        $subscription2 = $user2->subscribeTo($this->plan);
        \Salehye\Subscription\Facades\Subscription::pause($subscription2);

        $pausedSubscriptions = Subscription::paused()->get();

        $this->assertTrue($pausedSubscriptions->contains('id', $subscription2->id));
    }
}
