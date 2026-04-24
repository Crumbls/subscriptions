---
title: Subscribe and enforce limits
weight: 30
---

With a subscriber model, plans, and features in place, you can subscribe and start gating features.

## Subscribe

```php
$tenant->subscribe('main', $pro);

// Or the longer-named original:
$tenant->newPlanSubscription('main', $pro);

// Optional start date (defaults to now):
$tenant->subscribe('main', $pro, now()->addDay());
```

The `slug` (`'main'` here) is unique per subscriber, so you can run multiple subscriptions side-by-side: `'main'`, `'addon-storage'`, `'beta-features'`, etc.

## Check status

```php
$tenant->subscribedTo($pro->id);          // bool
$tenant->hasActiveSubscription();         // bool
$sub = $tenant->planSubscription('main'); // by slug
$sub = $tenant->currentSubscription();    // most recent active
```

A subscription has rich state methods:

```php
$sub->active();               // not ended, or on trial / grace
$sub->onTrial();              // currently in trial period
$sub->onGracePeriod();        // ended, but within grace window
$sub->canceled();             // has been canceled
$sub->ended();                // period has expired
$sub->inactive();             // opposite of active
$sub->pendingCancellation();  // canceled but period still has time left
$sub->daysUntilEnd();         // int or null
$sub->daysUntilTrialEnd();    // int or null
```

## Enforce a feature limit

The bread-and-butter pattern: check before letting the user do the thing.

```php
$sub = $tenant->currentSubscription();

if (! $sub->canUseFeature('users')) {
    throw new \Exception('User limit reached for your plan.');
}

// ... add the user ...

$sub->recordFeatureUsage('users');     // +1
```

The other usage methods:

```php
$sub->recordFeatureUsage('api-requests');           // +1
$sub->recordFeatureUsage('api-requests', 5);        // +5
$sub->recordFeatureUsage('api-requests', 10, false); // SET to 10 (incremental=false)
$sub->reduceFeatureUsage('users', 1);
$sub->getFeatureUsage('api-requests');               // int -- current count
$sub->getFeatureRemainings('api-requests');          // int -- value - used
$sub->getFeatureValue('api-requests');               // raw pivot value
```

## What if the feature isn't on the plan?

`recordFeatureUsage` with a slug that isn't attached throws `Crumbls\Subscriptions\Exceptions\UnknownFeatureException`. The exception exposes `featureSlug` and `plan` as readonly properties, so you can render a meaningful error or upsell prompt.

## Lifecycle

```php
$sub->changePlan($newPlan);                // switch plans
$sub->renew();                             // start a new period
$sub->cancel();                            // cancel at end of current period
$sub->cancel(immediately: true);           // cancel right now
$sub->reactivate();                        // undo a pending cancel
```

Each of these dispatches an event you can hook -- see [Events](/documentation/subscriptions/v2/events).

## Next

- [Plans](/documentation/subscriptions/v2/plans) -- pricing, billing, trial / grace, subscriber limits
- [Features](/documentation/subscriptions/v2/features) -- creating, attaching, usage tracking, resets
- [Middleware](/documentation/subscriptions/v2/middleware) -- gate routes by feature or subscription
