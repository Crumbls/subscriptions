# Changelog

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
