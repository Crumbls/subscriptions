# Changelog

## v2.0.0 — 2026-04-23

### Breaking

- `plan_subscriptions.slug` is no longer globally unique. It is now unique per subscriber: `(subscriber_type, subscriber_id, slug)`. This unblocks polymorphic use — two different subscriber types (e.g. `User` and `Team`) can now both hold a `main` subscription. Existing consumers must rebuild the unique index. See `UPGRADING.md`.
- Dropped unused `prorate_day`, `prorate_period`, `prorate_extend_due` columns from the `plans` table. These were never read or written by any code path.
- `SubscriptionCreated`, `SubscriptionCanceled`, `SubscriptionRenewed`, and `SubscriptionPlanChanged` no longer implement `ShouldBroadcast`. `broadcastOn()` was returning `[]` so nothing was ever broadcast. The `subscriptions.broadcast_events` config key has been removed. Consumers who want broadcasting should extend these events in their own application.
- `PlanSubscription::setNewPeriod()` signature changed from `(Interval|string $invoiceInterval = '', int $invoicePeriod = 0, Carbon|string $start = '')` to `(?Interval $invoiceInterval = null, ?int $invoicePeriod = null, ?Carbon $start = null)`. Only relevant if you were subclassing `PlanSubscription`.
- `PlanSubscription::recordFeatureUsage()` now throws `Crumbls\Subscriptions\Exceptions\UnknownFeatureException` instead of `Illuminate\Database\Eloquent\ModelNotFoundException` when called with a feature slug that isn't on the subscription's plan.

### Fixed

- `PlanSubscription::active()` no longer contained an unreachable branch. Behavior is unchanged for all documented states.
- Feature usage increments are now wrapped in a `DB::transaction()` with `lockForUpdate()`. Two concurrent writers can no longer lose a usage increment.
- `newPlanSubscription()` now locks the plan row during subscriber-limit enforcement. Two concurrent subscribers to a plan with `active_subscribers_limit = N` can no longer both squeeze past the check.
- `PlanSubscriptionUsage::scopeByFeatureSlug` now filters via a single subquery instead of fetching the feature row first.
- `subscriptions:prune` now prunes naturally-expired subscriptions too (previously only canceled + expired were pruned), matching the command description.

### Added

- Composite index on `(subscriber_type, subscriber_id, slug)` and plain index on `ends_at` in the `plan_subscriptions` table.
- `Feature::scopeBySlug()` and `Feature::hasReset()` helpers.
- `HasPlanSubscriptions::subscribe()` — friendly alias for `newPlanSubscription()`.
- Model factories (`PlanFactory`, `FeatureFactory`, `PlanSubscriptionFactory`, `PlanSubscriptionUsageFactory`) with useful state methods (`free()`, `paid()`, `withTrial()`, `withGrace()`, `limitedTo()`, `ended()`, `canceled()`, `onTrial()`, `resettableMonthly()`, `resettableDaily()`).
- GitHub Actions workflows for tests (PHP 8.3/8.4 × Laravel 11/12/13) and static analysis (Pint, PHPStan, Rector).
- Dependabot config, Pint config, `CONTRIBUTING.md`, `SECURITY.md`, issue and PR templates.

## v1.1.0 — 2026-04-22

- Add Laravel 13 support (`illuminate/*: ^13.0`, `orchestra/testbench: ^11.0`)
- Bump minimum PHP to 8.3 (required by Laravel 13)
- Widen spatie constraints: `eloquent-sortable ^4.4 || ^5.0`, `laravel-sluggable ^3.8`, `laravel-translatable ^6.13`
- Widen dev constraints: Pest 3|4, pest-plugin-laravel 3|4, PHPUnit 11|12
- Bump `driftingly/rector-laravel` to `^2.3` and add Laravel 12/13 Rector sets; switch Rector PHP set to 8.3

## v1.0.0 — 2026-02-20

### Breaking Changes (from rinvex/laravel-subscriptions)
- Namespace changed from `Rinvex\Subscriptions` to `Crumbls\Subscriptions`
- Config key changed from `rinvex.subscriptions` to `subscriptions`
- Removed `rinvex/laravel-support` dependency entirely
- Removed custom artisan commands (`rinvex:migrate`, `rinvex:publish`, `rinvex:rollback`) — use standard `migrate` / `vendor:publish`
- Removed model-level validation (ValidatingTrait) — use form requests instead

### Added
- `Interval` backed enum (`hour`, `day`, `week`, `month`, `year`) with `addToDate()` helper
- Grace period support — `onGracePeriod()` method and active check
- Lifecycle events: `SubscriptionCreated`, `SubscriptionCanceled`, `SubscriptionRenewed`, `SubscriptionPlanChanged`
- `subscriptions:prune` artisan command for cleaning up expired subscriptions
- `@property` docblocks on all models for IDE and static analysis support
- PHPStan (level 5) + Larastan — clean
- Pest test suite — 48 tests, 105 assertions (SQLite in-memory)

### Modernized
- Requires PHP 8.2+ and Laravel 11/12
- `casts()` method instead of `$casts` property (Laravel 11+ convention)
- Anonymous class migrations with `$table->id()` and `foreignId()->constrained()`
- Constructor promotion, typed properties, `static` return types
- Uses Spatie packages directly (`HasSlug`, `HasTranslations`, `SortableTrait`)
- Void return types on closures, modern PHPDoc generics
- Rector (Laravel Shift rules) applied for full Laravel 11 compliance
