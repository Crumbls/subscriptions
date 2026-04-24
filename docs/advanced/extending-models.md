---
title: Extending models
weight: 10
---

Every package model is resolved through `config/subscriptions.php`. Extending a model means: subclass the base, swap the config, done.

## Subclass

```php
namespace App\Models;

use Crumbls\Subscriptions\Models\Plan as BasePlan;

class Plan extends BasePlan
{
    public function cancel(bool $immediately = false): static
    {
        // Cancel the recurring payment with your provider first.
        $this->subscriber->paymentProvider()->cancelSubscription($this);

        return parent::cancel($immediately);
    }
}
```

## Register in config

```php
// config/subscriptions.php
'models' => [
    'plan' => \App\Models\Plan::class,
],
```

That's it. From this point:

- `Plan::query()` returns your subclass
- `$tenant->planSubscriptions()->first()->plan` returns your subclass
- The Filament panel (`crumbls/subscriptions-filament`) uses your subclass automatically
- The `subscriptions:prune` command operates on your subclass
- All scopes (`Plan::active()`, `Plan::free()`) work as before

## What to extend

Common reasons:

- **Hook into lifecycle**: override `cancel()`, `renew()`, `changePlan()` to call your payment provider or sync to a CRM
- **Add scopes**: `Plan::popular()`, `Plan::newSignupsOnly()`
- **Add accessors**: `$plan->display_price` formatted with currency symbol
- **Add relationships**: `$plan->testimonials()`, `$subscription->invoices()`
- **Override observers**: per-app validation that should run before save

## What not to do

- **Don't redefine `$table`** unless you also rename the table in `tables` config -- they have to match.
- **Don't change relationship method names** (`features`, `subscriptions`, `subscriber`). The trait and middleware reference them by name.
- **Don't remove soft deletes**. The prune command and `Plan::active()` scope depend on `deleted_at`.

## Adding new fields

If you add columns to the underlying tables (publish the migrations and edit), declare them in your subclass:

```php
class Plan extends BasePlan
{
    protected $fillable = [
        ...parent::FILLABLE,   // include the base fillable
        'is_featured',
        'display_color',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_featured' => 'boolean',
        ];
    }
}
```

Override `casts()` rather than the `$casts` property so you compose with the base instead of replacing it.
