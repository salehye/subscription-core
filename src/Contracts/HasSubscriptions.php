<?php

declare(strict_types=1);

namespace Salehye\Subscription\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Salehye\Subscription\Models\Subscription;

interface HasSubscriptions
{
    /**
     * @return MorphMany<Subscription>
     */
    public function subscriptions(): MorphMany;

    /**
     * Get the tenant ID for multi-tenancy support (optional).
     */
    public function getTenantId(): ?string;
}
