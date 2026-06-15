<?php

declare(strict_types=1);

namespace Salehye\Subscription\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Salehye\Subscription\Models\Plan;

interface PlanRepository
{
    public function findById(int $id): ?Plan;

    public function findBySlug(string $slug): ?Plan;

    public function getActive(?string $tenantId = null): Collection;

    public function getAll(?string $tenantId = null): Collection;

    public function create(array $data): Plan;

    public function update(Plan $plan, array $data): Plan;

    public function delete(Plan $plan): bool;

    public function getFeatures(Plan $plan): Collection;
}
