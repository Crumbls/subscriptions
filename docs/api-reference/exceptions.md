---
title: Exceptions
weight: 20
---

Two domain exceptions, both under `Crumbls\Subscriptions\Exceptions`.

## `UnknownFeatureException`

Thrown when feature-usage methods reference a feature slug that isn't attached to the subscription's plan.

```php
try {
    $sub->recordFeatureUsage('telephone-support');
} catch (\Crumbls\Subscriptions\Exceptions\UnknownFeatureException $e) {
    $e->featureSlug;  // 'telephone-support'
    $e->plan;         // the Plan instance
}
```

`featureSlug` and `plan` are public readonly properties so handlers can render meaningful errors or upsell prompts:

```php
return response()->view('upsell', [
    'missing_feature' => $e->featureSlug,
    'current_plan'    => $e->plan,
], 402);   // 402 Payment Required
```

When this is thrown:

- `recordFeatureUsage($slug, ...)` for a slug not attached to the plan
- `reduceFeatureUsage($slug, ...)` same
- `canUseFeature($slug)` does **not** throw -- it returns `false`. Use this for soft checks where the feature might legitimately not exist on this plan.

## `SubscriberLimitReachedException`

Thrown when `subscribe()` would push a plan past its `active_subscribers_limit`.

```php
try {
    $tenant->subscribe('main', $betaPlan);
} catch (\Crumbls\Subscriptions\Exceptions\SubscriberLimitReachedException $e) {
    return back()->withErrors([
        'plan' => 'This plan is at capacity. Join the waitlist?',
    ]);
}
```

Both exceptions extend `\Exception`. They are not `RuntimeException` or any framework class -- catch them by their concrete type.
