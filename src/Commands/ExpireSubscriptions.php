<?php

declare(strict_types=1);

namespace Salehye\Subscription\Commands;

use Illuminate\Console\Command;
use Salehye\Subscription\Enums\SubscriptionStatus;
use Salehye\Subscription\Events\SubscriptionExpired;
use Salehye\Subscription\Repositories\EloquentSubscriptionRepository;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscription:expire
        {--dry-run : Simulate the expiration without making changes}';

    protected $description = 'Expire subscriptions that have passed their end date.';

    public function __construct(
        protected EloquentSubscriptionRepository $subscriptionRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = $this->subscriptionRepository->findExpired();
        $count = $expired->count();

        if ($count === 0) {
            $this->info('No expired subscriptions found.');

            return self::SUCCESS;
        }

        $this->warn("Found {$count} expired subscription(s).");

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Plan', 'Subscriber', 'Ended At'],
                $expired->map(fn($s) => [
                    $s->id,
                    $s->plan->name,
                    "{$s->subscriber_type}#{$s->subscriber_id}",
                    $s->ends_at?->toDateTimeString(),
                ]),
            );

            $this->info('Dry run completed. No changes were made.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($expired as $subscription) {
            $this->subscriptionRepository->update($subscription, [
                'status' => SubscriptionStatus::Expired->value,
            ]);

            event(new SubscriptionExpired($subscription));

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
