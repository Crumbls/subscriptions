---
title: Pruning expired subscriptions
weight: 30
---

The `subscriptions:prune` artisan command soft-deletes subscriptions that ended a long time ago. It is the only built-in maintenance command.

## Usage

```bash
php artisan subscriptions:prune              # soft-delete subs ended > 30 days ago
php artisan subscriptions:prune --days=90    # custom threshold
php artisan subscriptions:prune --force      # skip the confirmation prompt
```

## What it does

For every `PlanSubscription` where `ends_at < now()->subDays($days)` and that isn't already soft-deleted, set `deleted_at = now()`. The associated `plan_subscription_usage` rows are not touched -- they cascade-soft-delete via the model's relationship.

The original rows stay in the table; you can `restore()` them or query `withTrashed()`. To remove them permanently, run a separate query:

```php
PlanSubscription::onlyTrashed()
    ->where('ends_at', '<', now()->subYear())
    ->forceDelete();
```

## Scheduling

Run it daily from the scheduler:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:prune --force')->daily();
```

`--force` is required for scheduled runs since the prompt would otherwise hang the scheduler.

## Tuning the threshold

`30 days` is the default. Pick the threshold based on:

- **How long do you keep customer history visible in admin tools?** Soft-deleted subs disappear from default queries.
- **Compliance constraints.** Some regimes require retaining subscription history for N years -- in that case, run prune with `--days=2555` (7 years) or skip pruning entirely.
- **Database size.** If `plan_subscriptions` is approaching a million-row regime, more aggressive pruning helps query performance.

## Auditing what would be pruned

There is no `--dry-run` flag. To preview, query directly:

```php
$count = PlanSubscription::where('ends_at', '<', now()->subDays(30))
    ->whereNull('deleted_at')
    ->count();

echo "Would prune {$count} subscriptions.\n";
```
