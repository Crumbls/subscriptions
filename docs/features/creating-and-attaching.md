---
title: Creating and attaching
weight: 10
---

## Create a feature

```php
use Crumbls\Subscriptions\Models\Feature;

$users = Feature::create([
    'name' => 'Users',
    'slug' => 'users',
]);
```

The `slug` is what you'll reference everywhere else (`recordFeatureUsage('users')`, `canUseFeature('users')`, middleware `can-use-feature:users`). Keep it short, kebab-case, and stable -- changing it later requires a data migration.

`name` is translatable JSON via `spatie/laravel-translatable`.

## Attach to plans with per-plan values

```php
$basic->features()->attach($users, ['value' => '5']);
$pro->features()->attach($users,   ['value' => '50']);
$ent->features()->attach($users,   ['value' => '999999']);
```

The `value` is stored as a string on the `plan_features` pivot. The library coerces it to int when checking limits and to bool when used as a feature flag.

## Boolean features (on/off)

For features that are either present or absent (SSL, custom branding, white-label, priority support):

```php
$ssl = Feature::create(['name' => 'SSL', 'slug' => 'ssl']);

$basic->features()->attach($ssl, ['value' => 'false']);
$pro->features()->attach($ssl,   ['value' => 'true']);
```

Then check with `canUseFeature`:

```php
if ($sub->canUseFeature('ssl')) {
    // enable SSL
}
```

`true`, non-zero numbers, and any non-empty string other than `'false'` / `'0'` count as enabled.

## Quota features (numeric limits)

For features with a count: users, API calls, projects, GB.

```php
$apiCalls = Feature::create([
    'name'                => 'API Requests',
    'slug'                => 'api-requests',
    'resettable_period'   => 1,
    'resettable_interval' => 'month',
]);

$pro->features()->attach($apiCalls, ['value' => '10000']);
```

`canUseFeature('api-requests')` returns true while `used < value`. See [Usage tracking](/documentation/subscriptions/v2/features/usage-tracking) for the consumption side.

## Sort order on the pivot

The pivot has its own `sort_order` so you can control how features appear on a single plan's pricing card without affecting their order on other plans:

```php
$pro->features()->attach($users,    ['value' => '50',  'sort_order' => 10]);
$pro->features()->attach($apiCalls, ['value' => '10k', 'sort_order' => 20]);
$pro->features()->attach($ssl,      ['value' => 'true','sort_order' => 30]);
```

## Detaching

Detaching removes the feature from the plan but keeps the feature definition for use on other plans:

```php
$basic->features()->detach($ssl);
```

Existing subscriptions on the basic plan immediately lose access to `ssl`. `canUseFeature('ssl')` returns false.

## Soft deletes

Features use soft deletes. A deleted feature stays out of new attachments and reports `false` for `canUseFeature` everywhere it was attached. Restore with `$feature->restore()`.
