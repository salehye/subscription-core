<?php

declare(strict_types=1);

namespace Salehye\Subscription\Tests\Unit;

use Salehye\Subscription\Enums\BillingCycle;
use Salehye\Subscription\Models\Feature;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Tests\TestCase;

class PlanTest extends TestCase
{
    public function test_can_create_plan(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'A test plan',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 19.99,
            'trial_days' => 7,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertNotNull($plan);
        $this->assertEquals('Test Plan', $plan->name);
        $this->assertEquals('test-plan', $plan->slug);
        $this->assertEquals(19.99, (float) $plan->price);
        $this->assertEquals(7, $plan->trial_days);
    }

    public function test_billing_cycle_enum(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Monthly Plan',
            'slug' => 'monthly-plan',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 9.99,
            'sort_order' => 1,
        ]);

        $this->assertEquals(BillingCycle::Monthly, $plan->getBillingCycleEnum());
        $this->assertEquals(30, $plan->getBillingCycleEnum()->days());
    }

    public function test_yearly_billing_days(): void
    {
        $this->assertEquals(365, BillingCycle::Yearly->days());
    }

    public function test_lifetime_billing_days(): void
    {
        $this->assertEquals(36500, BillingCycle::Lifetime->days());
    }

    public function test_can_attach_features(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 29.99,
            'sort_order' => 1,
        ]);

        $feature = Feature::query()->create([
            'name' => 'Max Users',
            'slug' => 'max_users',
            'type' => 'limit',
        ]);

        $plan->features()->attach($feature->id, ['value' => '50']);

        $this->assertTrue($plan->features->contains($feature));
        $this->assertEquals('50', $plan->getFeatureValue('max_users'));
    }

    public function test_scope_active(): void
    {
        Plan::query()->create([
            'name' => 'Active Plan',
            'slug' => 'active-plan',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Plan::query()->create([
            'name' => 'Inactive Plan',
            'slug' => 'inactive-plan',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 20,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $activePlans = Plan::query()->active()->get();

        $this->assertCount(1, $activePlans);
        $this->assertEquals('Active Plan', $activePlans->first()->name);
    }

    public function test_soft_delete(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 5,
            'sort_order' => 1,
        ]);

        $plan->delete();

        $this->assertNotNull($plan->deleted_at);
        $this->assertNull(Plan::query()->find($plan->id));
    }
}
