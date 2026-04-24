---
title: Middleware
weight: 70
---

The package ships two route middleware aliases for gating access by feature or by subscription status.

## `can-use-feature:{slug}` -- gate by feature availability

```php
Route::middleware('can-use-feature:api-requests')->group(function () {
    Route::post('/api/things', [ThingController::class, 'store']);
});
```

The middleware:

1. Reads the authenticated user's current subscription (`auth()->user()->currentSubscription()`)
2. Calls `$sub->canUseFeature('api-requests')`
3. Aborts with 403 if the feature is unavailable, otherwise hands off to the controller

You can pin the check to a specific subscription slug with a second argument:

```php
Route::middleware('can-use-feature:api-requests,pro')->group(/* ... */);
```

This calls `$user->planSubscription('pro')->canUseFeature('api-requests')` instead of `currentSubscription()`. Useful when a user runs multiple subscriptions and only one of them grants the feature.

## `subscribed[:{plan-slug}]` -- gate by subscription presence

```php
// Any active subscription
Route::middleware('subscribed')->group(/* ... */);

// Specifically subscribed to the 'pro' plan
Route::middleware('subscribed:pro')->group(/* ... */);
```

`subscribed` (no args) requires `$user->hasActiveSubscription()` to be true.

`subscribed:pro` requires the user to hold an active subscription whose plan slug is `pro`.

## What the middleware assumes

- The route is behind the `auth` middleware (so `auth()->user()` is set)
- The authenticated user model uses the `HasPlanSubscriptions` trait
- For feature checks, the feature is `attach()`ed to the relevant plan with a `value` that evaluates as enabled / non-zero

If any of these is false, the middleware short-circuits with 403 -- not a 500. The middleware never throws.

## Combining

```php
Route::middleware(['auth', 'subscribed', 'can-use-feature:premium-export'])
    ->post('/exports/premium', ExportController::class);
```

Run cheap checks first (`auth`, `subscribed`) and feature checks last so unauthorized requests don't pay for a feature lookup.

## Customizing the response

The default 403 is plain. To return JSON or render a custom view, publish the middleware via `composer copy:middleware`... actually no -- this isn't supported out of the box. Catch the abort upstream in your exception handler:

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (HttpException $e, Request $request) {
        if ($e->getStatusCode() === 403 && $request->is('api/*')) {
            return response()->json(['error' => 'Subscription does not include this feature.'], 403);
        }
    });
});
```
