---
title: Usage tracking
weight: 20
---

For numeric / quota features, the package tracks per-subscription consumption in the `plan_subscription_usage` table and resets on a configurable interval.

## Recording usage

```php
$sub->recordFeatureUsage('api-requests');           // increment by 1
$sub->recordFeatureUsage('api-requests', 5);        // increment by 5
$sub->recordFeatureUsage('api-requests', 10, false); // SET to 10 (not increment)
$sub->reduceFeatureUsage('users', 1);
```

The third argument is `$incremental`. Default is `true` (add to current). Set `false` to overwrite -- useful for "current user count" style features where the source of truth is a `count(*)` somewhere else.

If the feature isn't attached to the subscription's plan, this throws `Crumbls\Subscriptions\Exceptions\UnknownFeatureException`. The exception exposes `featureSlug` and `plan` as readonly properties so you can render an upsell prompt or a plan-mismatch error.

## Querying

```php
$sub->canUseFeature('api-requests');         // bool: used < value
$sub->getFeatureUsage('api-requests');       // int: current count
$sub->getFeatureRemainings('api-requests');  // int: value - used
$sub->getFeatureValue('api-requests');       // string: raw pivot value
```

`canUseFeature` is the right check before allowing the user to do the gated thing.

## Resets

A feature with a `resettable_period` and `resettable_interval` automatically resets its usage counter at the end of each interval:

```php
Feature::create([
    'slug'                => 'api-requests',
    'resettable_period'   => 1,
    'resettable_interval' => 'month',
]);
```

The usage row stores a `valid_until` column. When you call `recordFeatureUsage` after `valid_until` has passed, the count resets to 0 and `valid_until` advances by one interval. Resets are lazy -- they happen on next access, not via a scheduled job.

A `resettable_period` of `0` means "never reset" -- use this for running counts like "current user count" or "current project count" where you'll typically `reduceFeatureUsage` when the resource is removed.

## Pattern: enforce a hard limit

```php
$sub = $tenant->currentSubscription();

if (! $sub->canUseFeature('users')) {
    return back()->withErrors(['users' => 'User limit reached. Upgrade to add more.']);
}

DB::transaction(function () use ($tenant, $sub, $request) {
    $tenant->users()->create($request->validated());
    $sub->recordFeatureUsage('users');
});
```

## Pattern: sync from source of truth

For "current count" features, recompute periodically rather than incrementing on every event:

```php
$sub->recordFeatureUsage('users', $tenant->users()->count(), incremental: false);
```

Run this on a `daily()` scheduled job, or after bulk imports.

## Pattern: rate-limited API endpoint

```php
Route::middleware('can-use-feature:api-requests')->post('/api/things', function () {
    $sub = auth()->user()->currentSubscription();
    $sub->recordFeatureUsage('api-requests');

    // ... handle request ...
});
```

The middleware short-circuits with a 403 if the feature is unavailable; the controller only runs if there's quota.
