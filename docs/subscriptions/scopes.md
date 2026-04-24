---
title: Query scopes
weight: 20
---

## On `PlanSubscription`

```php
use Crumbls\Subscriptions\Models\PlanSubscription;

PlanSubscription::findActive()->get();
PlanSubscription::findEndingPeriod(7)->get();   // ending within 7 days
PlanSubscription::findEndedPeriod()->get();
PlanSubscription::findEndingTrial(3)->get();    // trial ending within 3 days
PlanSubscription::findEndedTrial()->get();
PlanSubscription::ofSubscriber($tenant)->get(); // scope by subscriber model
PlanSubscription::byPlanId($plan->id)->get();
```

`findActive()` matches the same definition as `$sub->active()`: not ended, or on trial, or on grace.

## On `Plan`

```php
use Crumbls\Subscriptions\Models\Plan;

Plan::active()->get();
Plan::inactive()->get();
Plan::free()->get();      // price = 0
Plan::paid()->get();      // price > 0
```

## Combining

Scopes chain like any Eloquent scope:

```php
$endingSoon = PlanSubscription::findActive()
    ->findEndingPeriod(3)
    ->where('subscriber_type', Tenant::class)
    ->with('subscriber', 'plan')
    ->get();
```

## Patterns

### Send "your trial ends soon" emails

```php
// routes/console.php
Schedule::call(function () {
    PlanSubscription::findActive()
        ->findEndingTrial(3)
        ->each(fn ($sub) => $sub->subscriber->notify(new TrialEndingSoon($sub)));
})->dailyAt('09:00');
```

### Build a "renewals next 30 days" report

```php
PlanSubscription::findActive()
    ->findEndingPeriod(30)
    ->whereDoesntHave('subscriber', fn ($q) => $q->where('cancel_pending', true))
    ->with('plan')
    ->get()
    ->groupBy('plan.name');
```
