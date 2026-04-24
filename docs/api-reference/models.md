---
title: Models
weight: 10
---

| Model | Class | Purpose |
|---|---|---|
| Plan | `Crumbls\Subscriptions\Models\Plan` | A subscription tier with pricing and billing cycle |
| Feature | `Crumbls\Subscriptions\Models\Feature` | A standalone feature (e.g. "Users", "API Calls") |
| PlanFeature | `Crumbls\Subscriptions\Models\PlanFeature` | Pivot linking features to plans with a per-plan `value` |
| PlanSubscription | `Crumbls\Subscriptions\Models\PlanSubscription` | A subscriber's subscription to a plan |
| PlanSubscriptionUsage | `Crumbls\Subscriptions\Models\PlanSubscriptionUsage` | Per-subscription consumption of a feature |

All models resolve through `config('subscriptions.models')` -- swap any of them via the config without changing application code. See [Extending models](/documentation/subscriptions/v2/advanced/extending-models).

## `Plan`

Common methods you'll call directly:

```php
$plan->features()                  // BelongsToMany Feature
$plan->subscriptions()             // HasMany PlanSubscription
$plan->isFree()                    // bool
$plan->isPaid()                    // bool
$plan->getFeatureBySlug('users')   // Feature|null
$plan->canHaveMoreSubscribers()    // bool, respects active_subscribers_limit
```

Scopes: `active()`, `inactive()`, `free()`, `paid()`.

## `Feature`

```php
$feature->plans()                  // BelongsToMany Plan
$feature->isResettable()           // bool, true if resettable_period > 0
```

## `PlanFeature` (pivot)

The pivot model holds the per-plan `value`. You usually access it through the relationship pivot:

```php
$pivot = $plan->features->first()->pivot;
$pivot->value;        // string -- the per-plan limit
$pivot->sort_order;   // int -- ordering on this plan
```

## `PlanSubscription`

The most-touched model. State and lifecycle methods live here.

```php
// State checks
$sub->active(); $sub->onTrial(); $sub->onGracePeriod(); $sub->canceled();
$sub->ended(); $sub->inactive(); $sub->pendingCancellation();
$sub->daysUntilEnd(); $sub->daysUntilTrialEnd();

// Lifecycle
$sub->changePlan($plan);
$sub->renew();
$sub->cancel(immediately: false);
$sub->reactivate();

// Feature usage
$sub->canUseFeature('slug');
$sub->getFeatureUsage('slug');
$sub->getFeatureRemainings('slug');
$sub->getFeatureValue('slug');
$sub->recordFeatureUsage('slug', $count = 1, $incremental = true);
$sub->reduceFeatureUsage('slug', $count = 1);

// Relationships
$sub->subscriber;     // morphTo -- the User/Tenant/etc.
$sub->plan;           // BelongsTo Plan
$sub->usage;          // HasMany PlanSubscriptionUsage
```

Scopes: `findActive()`, `findEndingPeriod($days)`, `findEndedPeriod()`, `findEndingTrial($days)`, `findEndedTrial()`, `ofSubscriber($model)`, `byPlanId($id)`.

## `PlanSubscriptionUsage`

Consumption tracking. You rarely instantiate this directly -- `PlanSubscription::recordFeatureUsage` manages it.

```php
$usage->subscription;   // BelongsTo PlanSubscription
$usage->feature;        // BelongsTo Feature
$usage->used;           // int -- current consumption
$usage->valid_until;    // Carbon|null -- when the count resets
```

## Trait

`Crumbls\Subscriptions\Traits\HasPlanSubscriptions` -- add to any subscriber model.

```php
$model->subscribe('slug', Plan $plan, ?Carbon $startsAt = null): PlanSubscription
$model->newPlanSubscription(...) // alias of subscribe
$model->subscribedTo(int $planId): bool
$model->hasActiveSubscription(): bool
$model->planSubscription(string $slug): ?PlanSubscription
$model->currentSubscription(): ?PlanSubscription
$model->planSubscriptions(): MorphMany
```
