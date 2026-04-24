<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Traits;

use Carbon\Carbon;
use Crumbls\Subscriptions\Exceptions\SubscriberLimitReachedException;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Crumbls\Subscriptions\Services\Period;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait HasPlanSubscriptions
{
    public function planSubscriptions(): MorphMany
    {
        return $this->morphMany(
            config('subscriptions.models.plan_subscription'),
            'subscriber',
            'subscriber_type',
            'subscriber_id',
        );
    }

    /**
     * Get all active subscriptions (not ended, or on trial/grace).
     *
     * Non-ended subs match directly in SQL. Grace-period subs (ended
     * but within the plan's grace window) need the per-row PHP check,
     * so they are filtered after hydration.
     */
    public function activePlanSubscriptions(): Collection
    {
        return $this->planSubscriptions()
            ->where(fn ($q) => $q
                ->where('ends_at', '>', now())
                ->orWhereNull('ends_at'))
            ->get()
            ->concat($this->graceCandidateSubscriptions())
            ->reject->inactive()
            ->values();
    }

    /**
     * Get a specific subscription by its slug.
     */
    public function planSubscription(string $subscriptionSlug): ?PlanSubscription
    {
        return $this->planSubscriptions()->where('slug', $subscriptionSlug)->first();
    }

    /**
     * Get the most recent active subscription regardless of slug.
     *
     * Uses the canonical `active()` definition, so a subscription on its
     * grace period is still "current".
     */
    public function currentSubscription(): ?PlanSubscription
    {
        return $this->activePlanSubscriptions()->sortByDesc('id')->first();
    }

    /**
     * Get all plans the subscriber is actively subscribed to.
     */
    public function subscribedPlans(): Collection
    {
        $planIds = $this->activePlanSubscriptions()->pluck('plan_id')->unique()->values();

        /** @var class-string<Plan> $model */
        $model = config('subscriptions.models.plan');

        return $model::whereIn('id', $planIds)->get();
    }

    /**
     * Check if the subscriber has an active subscription to the given plan.
     */
    public function subscribedTo(int $planId): bool
    {
        $subscription = $this->planSubscriptions()->where('plan_id', $planId)->first();

        return $subscription && $subscription->active();
    }

    /**
     * Check if the subscriber has any active subscription at all.
     */
    public function hasActiveSubscription(): bool
    {
        $hasNonEnded = $this->planSubscriptions()
            ->where('ends_at', '>', now())
            ->exists();

        if ($hasNonEnded) {
            return true;
        }

        return $this->graceCandidateSubscriptions()
            ->contains(fn (PlanSubscription $sub) => $sub->onGracePeriod());
    }

    /**
     * Subscriptions ended recently enough that they could plausibly still
     * be within a grace period. We use the largest grace window configured
     * on any of the subscriber's plans as the lookback horizon.
     */
    protected function graceCandidateSubscriptions(): Collection
    {
        return $this->planSubscriptions()
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->whereHas('plan', fn ($q) => $q->where('grace_period', '>', 0))
            ->get();
    }

    /**
     * Subscribe to a new plan. Friendly alias for {@see newPlanSubscription()}.
     *
     * @throws SubscriberLimitReachedException
     */
    public function subscribe(string $name, Plan $plan, ?Carbon $startDate = null): PlanSubscription
    {
        return $this->newPlanSubscription($name, $plan, $startDate);
    }

    /**
     * Subscribe to a new plan.
     *
     * @throws SubscriberLimitReachedException
     */
    public function newPlanSubscription(string $name, Plan $plan, ?Carbon $startDate = null): PlanSubscription
    {
        return DB::transaction(function () use ($name, $plan, $startDate): PlanSubscription {
            // Lock the plan row for the duration of the transaction so that concurrent
            // subscribers can't both squeeze past an active_subscribers_limit check.
            if ($plan->hasSubscriberLimit()) {
                $plan = $plan->newQuery()->lockForUpdate()->findOrFail($plan->getKey());
            }

            if (! $plan->canAcceptNewSubscriber()) {
                throw new SubscriberLimitReachedException(
                    $plan,
                    $plan->active_subscribers_limit,
                    $plan->activeSubscriberCount(),
                );
            }

            $trial = new Period($plan->trial_interval ?? 'day', $plan->trial_period ?? 0, $startDate ?? now());
            $period = new Period($plan->invoice_interval, $plan->invoice_period, $trial->getEndDate());

            return $this->planSubscriptions()->create([
                'name' => $name,
                'plan_id' => $plan->getKey(),
                'trial_ends_at' => $plan->hasTrial() ? $trial->getEndDate() : null,
                'starts_at' => $period->getStartDate(),
                'ends_at' => $period->getEndDate(),
            ]);
        });
    }
}
