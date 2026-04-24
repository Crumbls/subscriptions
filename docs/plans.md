---
title: Plans
weight: 40
---

A `Plan` represents a subscription tier -- pricing, billing cycle, trial / grace windows, and an optional subscriber cap.

## Pricing

```php
Plan::create([
    'name'        => 'Pro',
    'price'       => 9.99,
    'signup_fee'  => 1.99,
    'currency'    => 'USD',
]);
```

`currency` is a label, not a converter. The package never does FX. Display logic, multi-currency catalogs, and conversion belong in your app.

A plan with `price = 0` is a free plan. The `Plan::free()` and `Plan::paid()` scopes find them.

## Billing cycle

```php
'invoice_period'   => 1,
'invoice_interval' => 'month',  // hour | day | week | month | year
```

Together these define how long one billing period lasts. `1 month` is monthly, `1 year` is annual, `3 month` is quarterly. The interval enum is `Crumbls\Subscriptions\Enums\Interval`.

When a subscription is renewed (`$sub->renew()`), `starts_at` advances by exactly one billing period from the previous `ends_at`.

## Trial period

```php
'trial_period'   => 15,
'trial_interval' => 'day',
```

When a subscriber starts a subscription, `trial_ends_at` is set to `starts_at + trial_period trial_interval`. While `now() <= trial_ends_at`, `$sub->onTrial()` returns true.

A subscription on trial is considered active. Feature usage works normally.

Omit both `trial_*` fields for no trial.

## Grace period

```php
'grace_period'   => 7,
'grace_interval' => 'day',
```

After the billing period ends (`ends_at`), the subscription enters grace for `grace_period grace_interval`. During grace, `$sub->onGracePeriod()` returns true and `$sub->active()` is still true. Use this window to retry payment, send dunning emails, or block writes without revoking access.

Omit both `grace_*` fields for no grace window -- the subscription becomes inactive the moment `ends_at` passes.

## Subscriber limit

```php
'active_subscribers_limit' => 100,
```

Cap how many active subscribers can hold this plan at once. Useful for limited-availability beta plans or capacity-bounded white-glove tiers. Subscribing past the limit throws `Crumbls\Subscriptions\Exceptions\SubscriberLimitReachedException`.

`null` (default) means no limit.

## Active flag and sort order

```php
'is_active'  => true,
'sort_order' => 10,
```

`is_active = false` removes the plan from new-signup flows without deleting it. Existing subscriptions on the inactive plan keep working until they end or get switched. `Plan::active()` and `Plan::inactive()` scope queries.

`sort_order` drives display order on pricing pages and in the Filament panel. Spatie's eloquent-sortable trait does the heavy lifting.

## Translatable name and description

`name` and `description` are stored as JSON via `spatie/laravel-translatable`. Set per-locale values:

```php
$plan->setTranslation('name', 'en', 'Pro');
$plan->setTranslation('name', 'es', 'Profesional');
$plan->save();

app()->setLocale('es');
echo $plan->name;  // "Profesional"
```

## Soft deletes

Plans use soft deletes. A deleted plan keeps its row and its existing subscriptions; new signups are blocked. Restore with `$plan->restore()`. Use `$plan->forceDelete()` only when you're sure no historical record needs the row.
