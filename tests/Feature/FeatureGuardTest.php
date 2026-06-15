<?php

declare(strict_types=1);

namespace Salehye\Subscription\Tests\Feature;

use Carbon\Carbon;
use Salehye\Subscription\Enums\BillingCycle;
use Salehye\Subscription\Enums\FeatureType;
use Salehye\Subscription\Exceptions\FeatureLimitExceededException;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Services\FeatureGuard;
use Salehye\Subscription\Tests\TestCase;

class FeatureGuardTest extends TestCase
{
    protected Plan $plan;
    protected Feature $toggleFeature;
    protected Feature $consumableFeature;
    protected Feature $limitFeature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::query()->create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 19.99,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->toggleFeature = Feature::query()->create([
            'name' => 'API Access',
            'slug' => 'api_access',
            'type' => FeatureType::Toggle->value,
        ]);

        $this->consumableFeature = Feature::query()->create([
            'name' => 'Monthly Emails',
            'slug' => 'monthly_emails',
            'type' => FeatureType::Consumable->value,
        ]);

        $this->limitFeature = Feature::query()->create([
            'name' => 'Max Users',
            'slug' => 'max_users',
            'type' => FeatureType::Limit->value,
        ]);

        // Assign features to plan
        $this->plan->features()->attach([
            $this->toggleFeature->id => ['value' => 'true'],
            $this->consumableFeature->id => ['value' => '100'],
            $this->limitFeature->id => ['value' => '10'],
        ]);
    }

    public function test_can_access_toggle_feature(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);

        $this->assertTrue($guard->can($subscription, 'api_access'));
    }

    public function test_cannot_access_disabled_toggle(): void
    {
        $this->plan->features()->updateExistingPivot($this->toggleFeature->id, ['value' => 'false']);

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);

        $this->assertFalse($guard->can($subscription, 'api_access'));
    }

    public function test_can_consume_feature(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);
        $guard->consume($subscription, 'monthly_emails', 10);

        $this->assertEquals(90, $guard->remaining($subscription, 'monthly_emails'));
    }

    public function test_consumable_throws_when_limit_exceeded(): void
    {
        $this->expectException(FeatureLimitExceededException::class);

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);
        $guard->consume($subscription, 'monthly_emails', 150);
    }

    public function test_remaining_usage(): void
    {
        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);

        $this->assertEquals(100, $guard->remaining($subscription, 'monthly_emails'));

        $guard->consume($subscription, 'monthly_emails', 30);

        $this->assertEquals(70, $guard->remaining($subscription, 'monthly_emails'));
    }

    public function test_unlimited_feature(): void
    {
        $this->plan->features()->updateExistingPivot($this->consumableFeature->id, ['value' => 'unlimited']);

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);

        $this->assertNull($guard->limit($subscription, 'monthly_emails'));
        $this->assertEquals(PHP_INT_MAX, $guard->remaining($subscription, 'monthly_emails'));

        // Consuming should work fine
        $guard->consume($subscription, 'monthly_emails', 1000000);
        $this->assertEquals(1000000, $guard->usage($subscription, 'monthly_emails'));
    }

    public function test_feature_events_are_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = $this->createUser();
        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);
        $guard->consume($subscription, 'monthly_emails', 5);

        \Illuminate\Support\Facades\Event::assertDispatched(\Salehye\Subscription\Events\FeatureConsumed::class);
    }

    public function test_usage_resets_on_renewal(): void
    {
        $user = $this->createUser();
        Carbon::setTestNow(Carbon::create(2025, 1, 1));

        $subscription = $user->subscribeTo($this->plan);

        $guard = app(FeatureGuard::class);
        $guard->consume($subscription, 'monthly_emails', 40);

        $this->assertEquals(60, $guard->remaining($subscription, 'monthly_emails'));

        // Renew resets usage — need to refresh subscription to get fresh data
        \Salehye\Subscription\Facades\Subscription::renew($subscription);

        $guard = app(FeatureGuard::class);
        $this->assertEquals(100, $guard->remaining($subscription->fresh(), 'monthly_emails'));

        Carbon::setTestNow();
    }
}
