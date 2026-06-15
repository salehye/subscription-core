<?php

declare(strict_types=1);

namespace Salehye\Subscription\Commands;

use Illuminate\Console\Command;
use Salehye\Subscription\Repositories\EloquentSubscriptionRepository;
use Salehye\Subscription\Services\UsageTracker;

class ResetUsage extends Command
{
    protected $signature = 'subscription:reset-usage
        {--subscription= : Only reset usage for a specific subscription ID}
        {--dry-run : Simulate the reset without making changes}';

    protected $description = 'Reset usage tracking for subscriptions (typically called on renewal).';

    public function __construct(
        protected EloquentSubscriptionRepository $subscriptionRepository,
        protected UsageTracker $usageTracker,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $subscriptionId = $this->option('subscription');

        if ($subscriptionId) {
            $subscription = $this->subscriptionRepository->findById((int) $subscriptionId);

            if ($subscription === null) {
                $this->error("Subscription #{$subscriptionId} not found.");

                return self::FAILURE;
            }

            if ($this->option('dry-run')) {
                $this->warn("Would reset usage for subscription #{$subscription->id} ({$subscription->plan->name}).");

                return self::SUCCESS;
            }

            $this->usageTracker->reset($subscription);
            $this->info("Usage reset for subscription #{$subscription->id}.");

            return self::SUCCESS;
        }

        // Reset all expired subscriptions' usage
        $expired = $this->subscriptionRepository->findExpired();
        $count = $expired->count();

        if ($count === 0) {
            $this->info('No subscriptions to reset.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("Would reset usage for {$count} subscription(s).");

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($expired as $subscription) {
            $this->usageTracker->reset($subscription);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Usage reset for {$count} subscription(s).");

        return self::SUCCESS;
    }
}
