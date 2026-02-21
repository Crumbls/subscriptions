<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Models;

use Carbon\Carbon;
use Crumbls\Subscriptions\Enums\Interval;
use Crumbls\Subscriptions\Events\SubscriptionCanceled;
use Crumbls\Subscriptions\Events\SubscriptionCreated;
use Crumbls\Subscriptions\Events\SubscriptionPlanChanged;
use Crumbls\Subscriptions\Events\SubscriptionRenewed;
use Crumbls\Subscriptions\Services\Period;
use Crumbls\Subscriptions\Traits\BelongsToPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $subscriber_id
 * @property string $subscriber_type
 * @property int $plan_id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property \Carbon\Carbon|null $cancels_at
 * @property \Carbon\Carbon|null $canceled_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Plan $plan
 */
class PlanSubscription extends Model
{
    use BelongsToPlan, HasFactory, HasSlug, HasTranslations, SoftDeletes;

    protected $fillable = [
        'subscriber_id',
        'subscriber_type',
        'plan_id',
        'slug',
        'name',
        'description',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
        'canceled_at',
    ];

    public array $translatable = ['name', 'description'];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('subscriptions.tables.plan_subscriptions'));
        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            'subscriber_id' => 'integer',
            'plan_id' => 'integer',
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancels_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->starts_at || ! $model->ends_at) {
                $model->setNewPeriod();
            }
        });

        static::created(fn (self $sub) => SubscriptionCreated::dispatch($sub));
        static::deleted(fn (self $sub) => $sub->usage()->delete());
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // ── Relationships ────────────────────────────────────────────────

    public function subscriber(): MorphTo
    {
        return $this->morphTo('subscriber', 'subscriber_type', 'subscriber_id');
    }

    /** @return HasMany<PlanSubscriptionUsage, $this> */
    public function usage(): HasMany
    {
        /** @var class-string<PlanSubscriptionUsage> $model */
        $model = config('subscriptions.models.plan_subscription_usage', PlanSubscriptionUsage::class);

        return $this->hasMany($model, 'subscription_id');
    }

    // ── State Checks ─────────────────────────────────────────────────

    public function active(): bool
    {
        if ($this->ended() && $this->canceled()) {
            return false;
        }
        if (! $this->ended()) {
            return true;
        }
        if ($this->onTrial()) {
            return true;
        }
        return $this->onGracePeriod();
    }

    public function inactive(): bool
    {
        return ! $this->active();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && Carbon::now()->lt($this->trial_ends_at);
    }

    public function canceled(): bool
    {
        return $this->canceled_at && Carbon::now()->gte($this->canceled_at);
    }

    public function ended(): bool
    {
        return $this->ends_at && Carbon::now()->gte($this->ends_at);
    }

    public function onGracePeriod(): bool
    {
        if (! $this->ended() || ! $this->plan->hasGrace()) {
            return false;
        }

        $graceEnd = $this->ends_at->copy();
        $this->plan->grace_interval->addToDate($graceEnd, $this->plan->grace_period);

        return Carbon::now()->lt($graceEnd);
    }

    public function daysUntilEnd(): ?int
    {
        if (! $this->ends_at || $this->ended()) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->ends_at, absolute: false);
    }

    public function daysUntilTrialEnd(): ?int
    {
        if (! $this->trial_ends_at || ! $this->onTrial()) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->trial_ends_at, absolute: false);
    }

    /**
     * Check if cancellation is pending (canceled but not yet ended).
     */
    public function pendingCancellation(): bool
    {
        return $this->canceled_at !== null && ! $this->ended();
    }

    // ── Lifecycle Actions ────────────────────────────────────────────

    /**
     * Undo a pending cancellation. Only works if the subscription hasn't ended yet.
     */
    public function reactivate(): static
    {
        if ($this->ended()) {
            throw new LogicException('Cannot reactivate an ended subscription. Use renew() instead.');
        }

        $this->canceled_at = null;
        $this->cancels_at = null;
        $this->save();

        return $this;
    }

    public function cancel(bool $immediately = false): static
    {
        $this->canceled_at = Carbon::now();

        if ($immediately) {
            $this->ends_at = $this->canceled_at;
        }

        $this->save();

        SubscriptionCanceled::dispatch($this, $immediately);

        return $this;
    }

    public function changePlan(Plan $plan): static
    {
        $oldPlan = $this->plan;

        if ($oldPlan->invoice_interval !== $plan->invoice_interval
            || $oldPlan->invoice_period !== $plan->invoice_period) {
            $this->setNewPeriod($plan->invoice_interval, $plan->invoice_period);
            $this->usage()->delete();
        }

        $this->plan_id = $plan->getKey();
        $this->save();

        SubscriptionPlanChanged::dispatch($this, $oldPlan, $plan);

        return $this;
    }

    public function renew(): static
    {
        if ($this->ended() && $this->canceled()) {
            throw new LogicException('Unable to renew canceled ended subscription.');
        }

        DB::transaction(function (): void {
            $this->usage()->delete();
            $this->setNewPeriod();
            $this->canceled_at = null;
            $this->save();
        });

        SubscriptionRenewed::dispatch($this);

        return $this;
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeOfSubscriber(Builder $builder, Model $subscriber): Builder
    {
        return $builder
            ->where('subscriber_type', $subscriber->getMorphClass())
            ->where('subscriber_id', $subscriber->getKey());
    }

    public function scopeFindEndingTrial(Builder $builder, int $dayRange = 3): Builder
    {
        return $builder->whereBetween('trial_ends_at', [now(), now()->addDays($dayRange)]);
    }

    public function scopeFindEndedTrial(Builder $builder): Builder
    {
        return $builder->where('trial_ends_at', '<=', now());
    }

    public function scopeFindEndingPeriod(Builder $builder, int $dayRange = 3): Builder
    {
        return $builder->whereBetween('ends_at', [now(), now()->addDays($dayRange)]);
    }

    public function scopeFindEndedPeriod(Builder $builder): Builder
    {
        return $builder->where('ends_at', '<=', now());
    }

    public function scopeFindActive(Builder $builder): Builder
    {
        return $builder->where('ends_at', '>', now());
    }

    // ── Feature Usage ────────────────────────────────────────────────

    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true): PlanSubscriptionUsage
    {
        /** @var Feature $feature */
        $feature = $this->plan->features()->where('slug', $featureSlug)->firstOrFail();

        /** @var PlanSubscriptionUsage $usage */
        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature->getKey(),
        ]);

        if ($feature->resettable_period) {
            if (is_null($usage->valid_until)) {
                $usage->valid_until = $feature->getResetDate($this->created_at);
            } elseif ($usage->expired()) {
                $usage->valid_until = $feature->getResetDate($usage->valid_until);
                $usage->used = 0;
            }
        }

        $usage->used = $incremental ? $usage->used + $uses : $uses;
        $usage->save();

        return $usage;
    }

    public function reduceFeatureUsage(string $featureSlug, int $uses = 1): ?PlanSubscriptionUsage
    {
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        if (! $usage) {
            return null;
        }

        $usage->used = max($usage->used - $uses, 0);
        $usage->save();

        return $usage;
    }

    public function canUseFeature(string $featureSlug): bool
    {
        $featureValue = $this->getFeatureValue($featureSlug);

        if (is_null($featureValue) || $featureValue === '0' || $featureValue === 'false') {
            return false;
        }

        if ($featureValue === 'true') {
            return true;
        }

        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        if ($usage && $usage->expired()) {
            return true;
        }

        return $this->getFeatureRemainings($featureSlug) > 0;
    }

    public function getFeatureUsage(string $featureSlug): int
    {
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        return (! $usage || $usage->expired()) ? 0 : $usage->used;
    }

    public function getFeatureRemainings(string $featureSlug): int
    {
        return $this->getFeatureValue($featureSlug) - $this->getFeatureUsage($featureSlug);
    }

    public function getFeatureValue(string $featureSlug): mixed
    {
        /** @var Feature|null $feature */
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        return $feature?->pivot?->value;
    }

    // ── Internal ─────────────────────────────────────────────────────

    protected function setNewPeriod(
        Interval|string $invoiceInterval = '',
        int $invoicePeriod = 0,
        Carbon|string $start = '',
    ): static {
        if (empty($invoiceInterval)) {
            $invoiceInterval = $this->plan->invoice_interval;
        }

        if ($invoicePeriod === 0) {
            $invoicePeriod = $this->plan->invoice_period;
        }

        $period = new Period($invoiceInterval, $invoicePeriod, $start);

        $this->starts_at = $period->getStartDate();
        $this->ends_at = $period->getEndDate();

        return $this;
    }
}
