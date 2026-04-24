<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Models;

use Carbon\Carbon;
use Crumbls\Subscriptions\Database\Factories\FeatureFactory;
use Crumbls\Subscriptions\Enums\Interval;
use Crumbls\Subscriptions\Services\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
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
 * @property int $resettable_period
 * @property Interval|null $resettable_interval
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Feature extends Model implements Sortable
{
    use HasFactory, HasSlug, HasTranslations, SoftDeletes, SortableTrait;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'resettable_period',
        'resettable_interval',
        'sort_order',
    ];

    public array $translatable = ['name', 'description'];

    public array $sortable = ['order_column_name' => 'sort_order'];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('subscriptions.tables.features', 'features'));
        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            'resettable_period' => 'integer',
            'resettable_interval' => Interval::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (Feature $feature) => $feature->usage()->delete());
    }

    protected static function newFactory(): Factory
    {
        return FeatureFactory::new();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /** @return BelongsToMany<Plan, $this> */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            config('subscriptions.models.plan', Plan::class),
            config('subscriptions.tables.plan_features', 'plan_features'),
            'feature_id',
            'plan_id',
        )->withPivot('value', 'sort_order')->withTimestamps();
    }

    /** @return HasMany<PlanSubscriptionUsage, $this> */
    public function usage(): HasMany
    {
        return $this->hasMany(
            config('subscriptions.models.plan_subscription_usage', PlanSubscriptionUsage::class),
            'feature_id',
        );
    }

    public function getResetDate(Carbon $dateFrom): Carbon
    {
        return (new Period($this->resettable_interval, $this->resettable_period, $dateFrom))
            ->getEndDate();
    }

    public function scopeBySlug(Builder $builder, string $slug): Builder
    {
        return $builder->where('slug', $slug);
    }

    public function hasReset(): bool
    {
        return $this->resettable_period > 0;
    }
}
