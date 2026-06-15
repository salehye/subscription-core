<?php

declare(strict_types=1);

namespace Salehye\Subscription\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Salehye\Subscription\Contracts\HasSubscriptions;
use Salehye\Subscription\Models\Subscription;
use Salehye\Subscription\Models\SubscriptionUsage;

class EloquentSubscriptionRepository
{
    public function __construct(
        protected Subscription $model,
        protected SubscriptionUsage $usageModel,
    ) {
    }

    public function findById(int $id): ?Subscription
    {
        /** @var Subscription|null */
        return $this->model->newQuery()
            ->with(['plan', 'addons.plan', 'usage'])
            ->find($id);
    }

    public function findActiveForSubscriber(HasSubscriptions $subscriber): ?Subscription
    {
        /** @var Subscription|null */
        return $this->model->newQuery()
            ->where('subscriber_type', $subscriber->getMorphClass())
            ->where('subscriber_id', (string) $subscriber->getKey())
            ->where('type', 'primary')
            ->active()
            ->with(['plan.features', 'addons.plan.features'])
            ->latest('id')
            ->first();
    }

    public function getAllForSubscriber(HasSubscriptions $subscriber): Collection
    {
        return $this->model->newQuery()
            ->where('subscriber_type', $subscriber->getMorphClass())
            ->where('subscriber_id', (string) $subscriber->getKey())
            ->with(['plan', 'addons.plan'])
            ->latest('id')
            ->get();
    }

    public function create(array $data): Subscription
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);

        return $subscription->fresh();
    }

    public function findExpired(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', Carbon::now())
            ->get();
    }

    public function findExpiringSoon(int $days = 7): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', Carbon::now()->addDays($days))
            ->where('ends_at', '>', Carbon::now())
            ->get();
    }

    public function getUsageForPeriod(Subscription $subscription, string $featureSlug): ?SubscriptionUsage
    {
        /** @var SubscriptionUsage|null */
        return $this->usageModel->newQuery()
            ->where('subscription_id', $subscription->id)
            ->whereHas('feature', fn($q) => $q->where('slug', $featureSlug))
            ->currentPeriod($subscription)
            ->first();
    }

    public function createOrUpdateUsage(
        Subscription $subscription,
        int $featureId,
        int $used,
        Carbon $periodStart,
        ?Carbon $periodEnd = null,
    ): SubscriptionUsage {
        /** @var SubscriptionUsage */
        return $this->usageModel->newQuery()->updateOrCreate(
            [
                'subscription_id' => $subscription->id,
                'feature_id' => $featureId,
                'period_start' => $periodStart,
            ],
            [
                'used' => $used,
                'period_end' => $periodEnd,
            ],
        );
    }

    public function resetUsage(Subscription $subscription): void
    {
        $this->usageModel->newQuery()
            ->where('subscription_id', $subscription->id)
            ->delete();
    }
}
