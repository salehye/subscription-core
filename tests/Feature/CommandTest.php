<?php

declare(strict_types=1);

namespace Salehye\Subscription\Tests\Feature;

use Carbon\Carbon;
use Salehye\Subscription\Enums\BillingCycle;
use Salehye\Subscription\Enums\SubscriptionStatus;
use Salehye\Subscription\Models\Plan;
use Salehye\Subscription\Tests\TestCase;

class CommandTest extends TestCase
{
    public function test_expire_command_expires_past_subscriptions(): void
    {
        $user = $this->createUser();
        $plan = Plan::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $subscription = $user->subscribeTo($plan);

        // Move past end date
        Carbon::setTestNow(Carbon::create(2025, 3, 1));

        $this->artisan('subscription:expire')
            ->expectsOutputToContain('expired')
            ->assertExitCode(0);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Expired->value, $subscription->status);

        Carbon::setTestNow();
    }

    public function test_expire_command_dry_run(): void
    {
        $user = $this->createUser();
        $plan = Plan::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $subscription = $user->subscribeTo($plan);

        Carbon::setTestNow(Carbon::create(2025, 3, 1));

        $this->artisan('subscription:expire', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertExitCode(0);

        // Status should NOT have changed
        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Active->value, $subscription->status);

        Carbon::setTestNow();
    }

    public function test_expire_command_no_expired(): void
    {
        $this->artisan('subscription:expire')
            ->expectsOutputToContain('No expired')
            ->assertExitCode(0);
    }

    public function test_generate_recurring_command(): void
    {
        $user = $this->createUser();
        $plan = Plan::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'billing_cycle' => BillingCycle::Monthly->value,
            'price' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $user->subscribeTo($plan);

        Carbon::setTestNow(Carbon::create(2025, 1, 30));

        $this->artisan('subscription:invoices:generate')
            ->assertExitCode(0);

        Carbon::setTestNow();
    }
}
