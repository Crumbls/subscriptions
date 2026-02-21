<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $feature_id
 * @property int $used
 * @property \Carbon\Carbon|null $valid_until
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PlanSubscriptionUsage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'used',
        'valid_until',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('subscriptions.tables.plan_subscription_usage'));
        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            'subscription_id' => 'integer',
            'feature_id' => 'integer',
            'used' => 'integer',
            'valid_until' => 'datetime',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(config('subscriptions.models.feature', Feature::class), 'feature_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(config('subscriptions.models.plan_subscription'), 'subscription_id');
    }

    public function scopeByFeatureSlug(Builder $builder, string $featureSlug): Builder
    {
        $model = config('subscriptions.models.feature', Feature::class);
        $feature = $model::where('slug', $featureSlug)->first();

        return $builder->where('feature_id', $feature?->getKey());
    }

    public function expired(): bool
    {
        if (is_null($this->valid_until)) {
            return false;
        }

        return Carbon::now()->gte($this->valid_until);
    }
}
