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
     */
    public function activePlanSubscriptions(): Collection
    {
        return $this->planSubscriptions->reject->inactive();
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
     */
    public function currentSubscription(): ?PlanSubscription
    {
        return $this->planSubscriptions()
            ->where('ends_at', '>', now())
            ->latest('id')
            ->first();
    }

    /**
     * Get all plans the subscriber is actively subscribed to.
     */
    public function subscribedPlans(): Collection
    {
        $planIds = $this->planSubscriptions->reject->inactive()->pluck('plan_id')->unique();

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
        return $this->planSubscriptions->contains(fn ($sub) => $sub->active());
    }

    /**
     * Subscribe to a new plan.
     *
     * @throws SubscriberLimitReachedException
     */
    public function newPlanSubscription(string $subscription, Plan $plan, ?Carbon $startDate = null): PlanSubscription
    {
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
            'name' => $subscription,
            'plan_id' => $plan->getKey(),
            'trial_ends_at' => $plan->hasTrial() ? $trial->getEndDate() : null,
            'starts_at' => $period->getStartDate(),
            'ends_at' => $period->getEndDate(),
        ]);
    }
}
