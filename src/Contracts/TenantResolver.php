<?php

declare(strict_types=1);

namespace Salehye\Subscription\Contracts;

interface TenantResolver
{
    /**
     * Resolve the current tenant ID from the application context.
     */
    public function resolve(): ?string;
}
