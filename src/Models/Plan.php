<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Models;

use Crumbls\Subscriptions\Enums\Interval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property bool $is_active
 * @property string $price
 * @property string $signup_fee
 * @property string $currency
 * @property int $trial_period
 * @property Interval|null $trial_interval
 * @property int $invoice_period
 * @property Interval $invoice_interval
 * @property int $grace_period
 * @property Interval|null $grace_interval
 * @property int|null $active_subscribers_limit
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Plan extends Model implements Sortable
{
    use HasFactory, HasSlug, HasTranslations, SoftDeletes, SortableTrait;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'price',
        'signup_fee',
        'currency',
        'trial_period',
        'trial_interval',
        'invoice_period',
        'invoice_interval',
        'grace_period',
        'grace_interval',
        'active_subscribers_limit',
        'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public array $translatable = ['name', 'description'];

    public array $sortable = ['order_column_name' => 'sort_order'];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('subscriptions.tables.plans'));
        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'signup_fee' => 'decimal:2',
            'trial_period' => 'integer',
            'trial_interval' => Interval::class,
            'invoice_period' => 'integer',
            'invoice_interval' => Interval::class,
            'grace_period' => 'integer',
            'grace_interval' => Interval::class,
            'active_subscribers_limit' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (Plan $plan): void {
            $plan->features()->detach();
            $plan->subscriptions()->delete();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /** @return BelongsToMany<Feature, $this> */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            config('subscriptions.models.feature', Feature::class),
            config('subscriptions.tables.plan_features', 'plan_features'),
            'plan_id',
            'feature_id',
        )
            ->using(config('subscriptions.models.plan_feature', PlanFeature::class))
            ->withPivot('value', 'sort_order')
            ->withTimestamps();
    }

    /** @return HasMany<PlanSubscription, $this> */
    public function subscriptions(): HasMany
    {
        /** @var class-string<PlanSubscription> $model */
        $model = config('subscriptions.models.plan_subscription', PlanSubscription::class);

        return $this->hasMany($model, 'plan_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('is_active', true);
    }

    public function scopeInactive(Builder $builder): Builder
    {
        return $builder->where('is_active', false);
    }

    public function scopeFree(Builder $builder): Builder
    {
        return $builder->where('price', '<=', 0);
    }

    public function scopePaid(Builder $builder): Builder
    {
        return $builder->where('price', '>', 0);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isFree(): bool
    {
        return (float) $this->price <= 0.00;
    }

    public function hasTrial(): bool
    {
        return $this->trial_period && $this->trial_interval;
    }

    public function hasGrace(): bool
    {
        return $this->grace_period && $this->grace_interval;
    }

    public function hasSubscriberLimit(): bool
    {
        return $this->active_subscribers_limit !== null && $this->active_subscribers_limit > 0;
    }

    public function activeSubscriberCount(): int
    {
        return $this->subscriptions()->where('ends_at', '>', now())->count();
    }

    public function canAcceptNewSubscriber(): bool
    {
        if (! $this->hasSubscriberLimit()) {
            return true;
        }

        return $this->activeSubscriberCount() < $this->active_subscribers_limit;
    }

    public function getFeatureBySlug(string $featureSlug): ?Feature
    {
        return $this->features()->where('slug', $featureSlug)->first();
    }

    public function activate(): static
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    public function deactivate(): static
    {
        $this->update(['is_active' => false]);

        return $this;
    }
}
