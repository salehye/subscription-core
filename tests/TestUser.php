<?php

declare(strict_types=1);

namespace Salehye\Subscription\Tests;

use Illuminate\Database\Eloquent\Model;
use Salehye\Subscription\Contracts\HasSubscriptions as HasSubscriptionsContract;
use Salehye\Subscription\Traits\HasSubscriptions;

class TestUser extends Model implements HasSubscriptionsContract
{
    use HasSubscriptions;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
    ];

    public function getTenantId(): ?string
    {
        return null;
    }
}
