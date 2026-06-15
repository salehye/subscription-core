<?php

declare(strict_types=1);

namespace Salehye\Subscription\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Salehye\Subscription\Contracts\PlanRepository;
use Salehye\Subscription\Models\Plan;

class EloquentPlanRepository implements PlanRepository
{
    public function __construct(
        protected Plan $model,
    ) {
    }

    public function findById(int $id): ?Plan
    {
        /** @var Plan|null */
        return $this->model->newQuery()->find($id);
    }

    public function findBySlug(string $slug): ?Plan
    {
        /** @var Plan|null */
        return $this->model->newQuery()->where('slug', $slug)->first();
    }

    public function getActive(?string $tenantId = null): Collection
    {
        $query = $this->model->newQuery()->active()->ordered();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    public function getAll(?string $tenantId = null): Collection
    {
        $query = $this->model->newQuery()->ordered();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    public function create(array $data): Plan
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        return $plan->fresh();
    }

    public function delete(Plan $plan): bool
    {
        return $plan->delete();
    }

    public function getFeatures(Plan $plan): Collection
    {
        return $plan->features()->get();
    }
}
