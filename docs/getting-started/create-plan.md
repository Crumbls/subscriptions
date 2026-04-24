---
title: Create plans and features
weight: 20
---

Plans hold pricing and billing-cycle config. Features are standalone -- create them once and attach to multiple plans with different per-plan values.

## Create a plan

```php
use Crumbls\Subscriptions\Models\Plan;

$pro = Plan::create([
    'name'              => 'Pro',
    'description'       => 'Pro plan',
    'price'             => 9.99,
    'signup_fee'        => 1.99,
    'currency'          => 'USD',
    'invoice_period'    => 1,
    'invoice_interval'  => 'month',     // hour | day | week | month | year
    'trial_period'      => 15,
    'trial_interval'    => 'day',
    'grace_period'      => 7,
    'grace_interval'    => 'day',
]);
```

Every period is optional. Omit `trial_*` for no trial, `grace_*` for no grace window. Omit `signup_fee` for no signup fee.

A plan with `price` of `0` is a free plan -- the `Plan::free()` scope finds them; the `Plan::paid()` scope finds the rest.

## Create features

Features represent capabilities or limits that vary by plan: number of users, GB of storage, API requests per month, SSL on/off, custom branding on/off.

```php
use Crumbls\Subscriptions\Models\Feature;

$users = Feature::create([
    'name'              => 'Users',
    'slug'              => 'users',
    'resettable_period' => 0,           // never resets -- running count
]);

$apiCalls = Feature::create([
    'name'                => 'API Requests',
    'slug'                => 'api-requests',
    'resettable_period'   => 1,
    'resettable_interval' => 'month',   // resets every month
]);

$ssl = Feature::create([
    'name' => 'SSL',
    'slug' => 'ssl',
]);
```

A feature is a definition. The actual limit (the per-plan `value`) lives on the pivot, not on the feature itself.

## Attach features to plans

This is where per-plan limits happen:

```php
// Basic: 5 users, 100 API calls / month, no SSL
$basic->features()->attach($users,    ['value' => '5']);
$basic->features()->attach($apiCalls, ['value' => '100']);

// Pro: 50 users, 10000 API calls / month, SSL on
$pro->features()->attach($users,    ['value' => '50']);
$pro->features()->attach($apiCalls, ['value' => '10000']);
$pro->features()->attach($ssl,      ['value' => 'true']);

// Enterprise: unlimited users, unlimited API calls, SSL on
$ent->features()->attach($users,    ['value' => '999999']);
$ent->features()->attach($apiCalls, ['value' => '999999']);
$ent->features()->attach($ssl,      ['value' => 'true']);
```

`value` is stored as a string. Cast on read if you need a numeric or boolean. The library compares numerics as integers when checking limits (`canUseFeature` etc.) and treats `true` / `1` / a non-zero number as "feature enabled" for boolean-style features.

There is no built-in concept of "unlimited" -- pick a sentinel like `999999` or `-1` and handle it in your code if you need it.

## Next

[Subscribe a model and enforce limits](/documentation/subscriptions/v2/getting-started/subscribe).
