---
title: Installation
weight: 20
---

## Requirements

- PHP 8.3 or 8.4
- Laravel 11, 12, or 13
- MySQL, PostgreSQL, or SQLite (anything Eloquent supports)

## Install the package

```bash
composer require crumbls/subscriptions
```

## Run migrations

Migrations autoload by default -- nothing to publish:

```bash
php artisan migrate
```

This creates five tables: `plans`, `features`, `plan_features`, `plan_subscriptions`, and `plan_subscription_usage`.

To take ownership of the migration files (e.g. to customize column types or add indexes), publish them:

```bash
php artisan vendor:publish --tag=subscriptions-migrations
```

Then disable autoload in `config/subscriptions.php`:

```php
'autoload_migrations' => false,
```

## Publish the config (optional)

The config file lets you swap models or rename tables:

```bash
php artisan vendor:publish --tag=subscriptions-config
```

See [Configuration](/documentation/subscriptions/v2/configuration) for the full knob list.

## Verify

```bash
php artisan tinker
>>> \Crumbls\Subscriptions\Models\Plan::count()
=> 0
```

If that returns `0` (or any integer), you're wired up. From here, see [Getting started](/documentation/subscriptions/v2/getting-started).
