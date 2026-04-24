# Upgrading

## From 1.x to 2.0

### 1. Update the `plan_subscriptions` unique constraint

The global `UNIQUE (slug)` on `plan_subscriptions` is replaced by a composite `UNIQUE (subscriber_type, subscriber_id, slug)`. Run a migration in your app:

```php
// database/migrations/2026_xx_xx_rescope_subscription_slug_unique.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('subscriptions.tables.plan_subscriptions', 'plan_subscriptions');

        Schema::table($table, function (Blueprint $table): void {
            $table->dropUnique([$table->getTable() . '_slug_unique']);
            $table->unique(['subscriber_type', 'subscriber_id', 'slug']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        $table = config('subscriptions.tables.plan_subscriptions', 'plan_subscriptions');

        Schema::table($table, function (Blueprint $table): void {
            $table->dropIndex([$table->getTable() . '_ends_at_index']);
            $table->dropUnique(['subscriber_type', 'subscriber_id', 'slug']);
            $table->unique('slug');
        });
    }
};
```

### 2. Drop unused proration columns from `plans`

```php
// database/migrations/2026_xx_xx_drop_prorate_columns_from_plans.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('subscriptions.tables.plans', 'plans'), function (Blueprint $table): void {
            $table->dropColumn(['prorate_day', 'prorate_period', 'prorate_extend_due']);
        });
    }

    public function down(): void
    {
        Schema::table(config('subscriptions.tables.plans', 'plans'), function (Blueprint $table): void {
            $table->unsignedTinyInteger('prorate_day')->nullable();
            $table->unsignedTinyInteger('prorate_period')->nullable();
            $table->unsignedTinyInteger('prorate_extend_due')->nullable();
        });
    }
};
```

> If you were reading these columns in your own application code, you'll need to restore them in a custom migration and keep them on your own schema.

### 3. Drop the `broadcast_events` config key

Open `config/subscriptions.php` and remove the `broadcast_events` key — it is no longer read. The subscription events no longer implement `ShouldBroadcast`. If you relied on broadcasting, extend the events in your own app and implement `ShouldBroadcast` yourself.

### 4. Catch `UnknownFeatureException` instead of `ModelNotFoundException`

`PlanSubscription::recordFeatureUsage()` now throws `Crumbls\Subscriptions\Exceptions\UnknownFeatureException` (extends `RuntimeException`) when the feature slug isn't attached to the subscription's plan. Update any `try`/`catch` blocks that were catching `ModelNotFoundException`.

### 5. Subclassing `PlanSubscription::setNewPeriod()`

If you override `setNewPeriod` in a custom subclass, note the signature changed:

```php
// Before
protected function setNewPeriod(
    Interval|string $invoiceInterval = '',
    int $invoicePeriod = 0,
    Carbon|string $start = '',
): static;

// After
protected function setNewPeriod(
    ?Interval $invoiceInterval = null,
    ?int $invoicePeriod = null,
    ?Carbon $start = null,
): static;
```

### 6. (Optional) Adopt the `subscribe()` alias

`newPlanSubscription($name, $plan)` still works. A friendlier alias is now available:

```php
$user->subscribe('main', $plan);
```
