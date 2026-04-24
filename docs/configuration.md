---
title: Configuration
weight: 90
---

Publish the config to take ownership of table names or model classes:

```bash
php artisan vendor:publish --tag=subscriptions-config
```

`config/subscriptions.php`:

```php
return [
    'autoload_migrations' => true,

    'tables' => [
        'plans'                   => 'plans',
        'features'                => 'features',
        'plan_features'           => 'plan_features',
        'plan_subscriptions'      => 'plan_subscriptions',
        'plan_subscription_usage' => 'plan_subscription_usage',
    ],

    'models' => [
        'plan'                    => \Crumbls\Subscriptions\Models\Plan::class,
        'feature'                 => \Crumbls\Subscriptions\Models\Feature::class,
        'plan_feature'            => \Crumbls\Subscriptions\Models\PlanFeature::class,
        'plan_subscription'       => \Crumbls\Subscriptions\Models\PlanSubscription::class,
        'plan_subscription_usage' => \Crumbls\Subscriptions\Models\PlanSubscriptionUsage::class,
    ],
];
```

## `autoload_migrations`

When `true` (default), the package's migrations run as part of `php artisan migrate` without you publishing them. Set `false` if you want to own the migration files locally -- typically because you're customizing column types, adding indexes, or changing table names.

If you set `false`, also publish the migration files:

```bash
php artisan vendor:publish --tag=subscriptions-migrations
```

## `tables`

Rename the package's tables here if your app's naming convention requires it. The change applies everywhere -- model `$table` properties, foreign keys in migrations, and middleware queries all resolve through this config.

Common reasons to change:

- Existing app already has a `plans` table for something else (rename to `subscription_plans`)
- Multi-tenant DB schema where everything is prefixed (`tenant_plans`, `tenant_features`)
- Migrating from another package where tables had different names (preserve old names temporarily)

## `models`

Every model resolves through config. Swap in a custom model and the package uses it everywhere -- relationships, scopes, traits, middleware, and the prune command all go through the config-resolved class.

See [Extending models](/documentation/subscriptions/v2/advanced/extending-models) for the full pattern.
