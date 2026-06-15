<?php

declare(strict_types=1);

namespace Salehye\Subscription\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $feature_id
 * @property int $used
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon|null $period_end
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SubscriptionUsage extends Model
{
    protected $table = 'subscription_usage';

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'used',
        'period_start',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function scopeCurrentPeriod($query, Subscription $subscription)
    {
        return $query->where('subscription_id', $subscription->id)
            ->where('period_start', '<=', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('period_end')
                    ->orWhere('period_end', '>=', Carbon::now());
            });
    }

    public function scopeForFeature($query, Feature $feature)
    {
        return $query->where('feature_id', $feature->id);
    }
}
