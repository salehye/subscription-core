<?php

declare(strict_types=1);

namespace Salehye\Subscription\Commands;

use Illuminate\Console\Command;
use Salehye\Subscription\Enums\SubscriptionStatus;
use Salehye\Subscription\Repositories\EloquentSubscriptionRepository;
use Salehye\Subscription\Services\SubscriptionManagerImpl;

class CreateRecurringInvoices extends Command
{
    protected $signature = 'subscription:invoices:generate
        {--dry-run : Simulate invoice generation without making changes}';

    protected $description = 'Generate recurring invoices for active subscriptions nearing renewal.';

    public function __construct(
        protected EloquentSubscriptionRepository $subscriptionRepository,
        protected SubscriptionManagerImpl $subscriptionManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $expiringSoon = $this->subscriptionRepository->findExpiringSoon(1);
        $count = $expiringSoon->count();

        if ($count === 0) {
            $this->info('No subscriptions expiring soon.');

            return self::SUCCESS;
        }

        $this->warn("Found {$count} subscription(s) expiring soon.");

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Plan', 'Subscriber', 'Ends At'],
                $expiringSoon->map(fn($s) => [
                    $s->id,
                    $s->plan->name,
                    "{$s->subscriber_type}#{$s->subscriber_id}",
                    $s->ends_at?->toDateTimeString(),
                ]),
            );

            $this->info('Dry run completed. No invoices were generated.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($expiringSoon as $subscription) {
            if ($subscription->auto_renew) {
                $this->subscriptionManager->renew($subscription);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Processed {$count} subscription(s) for renewal.");

        return self::SUCCESS;
    }
}
