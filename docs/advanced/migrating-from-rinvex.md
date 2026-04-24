---
title: Migrating from rinvex
weight: 40
---

This package began as a modern reboot of `rinvex/laravel-subscriptions`. The mental model carries over; namespaces, config keys, and a few APIs differ.

## Namespace

| Rinvex | This package |
|---|---|
| `Rinvex\Subscriptions\Models\Plan` | `Crumbls\Subscriptions\Models\Plan` |
| `Rinvex\Subscriptions\Models\PlanFeature` | `Crumbls\Subscriptions\Models\PlanFeature` |
| `Rinvex\Subscriptions\Models\PlanSubscription` | `Crumbls\Subscriptions\Models\PlanSubscription` |
| `Rinvex\Subscriptions\Models\PlanSubscriptionUsage` | `Crumbls\Subscriptions\Models\PlanSubscriptionUsage` |
| (no equivalent -- features were inline) | `Crumbls\Subscriptions\Models\Feature` |
| `Rinvex\Subscriptions\Traits\HasPlanSubscriptions` | `Crumbls\Subscriptions\Traits\HasPlanSubscriptions` |

A search-and-replace on `Rinvex\Subscriptions` -> `Crumbls\Subscriptions` covers the bulk of an existing codebase.

## Config

The config key is `subscriptions` (was `rinvex.subscriptions`). The shape is similar but the table-name keys are lowercase singular slug-style (`plans`, `features`) instead of the rinvex `prefix + name` scheme.

## Features are now standalone

This is the biggest semantic change.

**Rinvex:** features were defined inline as a `plan_features` row attached to one specific plan. Reusing a feature across plans meant duplicating the definition.

**This package:** `Feature` is its own model with its own row. The `value` (per-plan limit) lives on the `plan_features` pivot.

Consequence: a single `users` Feature can be attached to `basic` (`value=5`), `pro` (`value=50`), and `enterprise` (`value=999999`), driven by one feature definition.

### Migration shape change

If you're carrying real data over from a Rinvex install:

1. Snapshot the old `plan_features` table.
2. Insert distinct feature definitions into the new `features` table (deduplicated by slug).
3. Repopulate the new `plan_features` pivot from the snapshot, linking by feature slug, copying the per-plan `value`.

There is no automatic migration script. The shape change is too app-specific (how to dedupe, what to do about feature-naming conflicts across plans) to ship a one-size-fits-all migration.

## Subscribing

```php
// Rinvex (still works in this package as an alias):
$user->newPlanSubscription('main', $plan);

// Preferred:
$user->subscribe('main', $plan);
```

Both methods take the same arguments. `subscribe()` is shorter and reads better.

## Events

**Rinvex:** trait-based hooks via `subscribed()`, `canceled()` model methods you'd override.

**This package:** four Laravel events:

- `SubscriptionCreated`
- `SubscriptionCanceled` (with `$immediate` flag)
- `SubscriptionRenewed`
- `SubscriptionPlanChanged` (with `$oldPlan` and `$newPlan`)

If you had `subscribed()` hooks in your Rinvex install, port them to event listeners. See [Events](/documentation/subscriptions/v2/events).

## What's gone

- **`rinvex:*` artisan commands.** Use plain `php artisan migrate`, `vendor:publish`, and the new `subscriptions:prune`.
- **Model-level validation.** Use Form Requests in your application.
- **Inline feature definitions on plans.** Features are now standalone.

## What's new

- **Features model** with translatable name and per-plan values
- **Lifecycle events** (`SubscriptionCreated` etc.)
- **Route middleware** (`can-use-feature`, `subscribed`)
- **Soft deletes** on plans, features, subscriptions, and usage
- **`subscriptions:prune` command** for ended-subscription cleanup
- **Spatie eloquent-sortable** integration for plan / feature ordering
- **Spatie laravel-translatable** integration for multi-locale plan names
- **Test factories** on every model
