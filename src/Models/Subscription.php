<?php

declare(strict_types=1);

namespace Salehye\Subscription\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Salehye\Subscription\Enums\SubscriptionStatus;

/**
 * @property int $id
 * @property string $subscriber_type
 * @property string $subscriber_id
 * @property string|null $tenant_id
 * @property int $plan_id
 * @property string $type (primary, addon)
 * @property int|null $parent_subscription_id
 * @property \Carbon\Carbon $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property string $status
 * @property \Carbon\Carbon|null $canceled_at
 * @property bool $auto_renew
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Subscription extends Model
{
    protected $fillable = [
        'subscriber_type',
        'subscriber_id',
        'tenant_id',
        'plan_id',
        'type',
        'parent_subscription_id',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'status',
        'canceled_at',
        'auto_renew',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function parentSubscription(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_subscription_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(self::class, 'parent_subscription_id');
    }

    public function usage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class, 'subscription_id');
    }

    public function getStatusEnum(): SubscriptionStatus
    {
        return SubscriptionStatus::from($this->status);
    }

    public function isActive(): bool
    {
        return $this->getStatusEnum()->isActive()
            && ($this->ends_at === null || $this->ends_at->isFuture())
            && !$this->isOnGracePeriod();
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isOnGracePeriod(): bool
    {
        if ($this->ends_at === null) {
            return false;
        }

        $graceDays = config('subscription.grace_period_days', 0);

        if ($graceDays <= 0) {
            return false;
        }

        return $this->ends_at->isPast()
            && $this->ends_at->copy()->addDays($graceDays)->isFuture();
    }

    public function isCanceled(): bool
    {
        return $this->status === SubscriptionStatus::Canceled->value;
    }

    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::Expired->value
            || ($this->ends_at !== null && $this->ends_at->isPast() && !$this->isOnGracePeriod());
    }

    public function isPrimary(): bool
    {
        return $this->type === 'primary';
    }

    public function isAddon(): bool
    {
        return $this->type === 'addon';
    }

    public function remainingDays(): int
    {
        if ($this->ends_at === null) {
            return PHP_INT_MAX;
        }

        return max(0, Carbon::now()->diffInDays($this->ends_at, false));
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            SubscriptionStatus::Active->value,
            SubscriptionStatus::Paused->value,
        ]);
    }

    public function scopePrimary($query)
    {
        return $query->where('type', 'primary');
    }

    public function scopeAddon($query)
    {
        return $query->where('type', 'addon');
    }

    public function scopeByTenant($query, ?string $tenantId)
    {
        if ($tenantId === null) {
            return $query->whereNull('tenant_id');
        }

        return $query->where('tenant_id', $tenantId);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', Carbon::now()->addDays($days));
    }
}
