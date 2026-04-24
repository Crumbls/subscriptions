---
title: Add the subscriber trait
weight: 10
---

Subscriptions are polymorphic, so any Eloquent model can subscribe -- User, Tenant, Team, Organization, Workspace, anything you want to bill.

Pull in `HasPlanSubscriptions` on the model that owns the subscription:

```php
use Crumbls\Subscriptions\Traits\HasPlanSubscriptions;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasPlanSubscriptions;
}
```

That's the only change to your model class. The trait adds:

- `subscribe(string $slug, Plan $plan, ?Carbon $startsAt = null): PlanSubscription`
- `newPlanSubscription(...)` -- alias of `subscribe()` for clarity
- `subscribedTo(int $planId): bool`
- `hasActiveSubscription(): bool`
- `planSubscription(string $slug): ?PlanSubscription`
- `currentSubscription(): ?PlanSubscription` -- most recent active
- `planSubscriptions()` -- HasMany relationship to `PlanSubscription`

## Subscription slugs are scoped to the subscriber

Each subscription has a `slug` that is unique **per subscriber**, not globally. This means one app can run multiple subscriptions per subscriber (e.g. a `main` plan and a `addon-storage` plan side-by-side), and different subscriber types (User vs Tenant) can both hold a `main` subscription without colliding.

```php
$tenant->subscribe('main', $proPlan);
$tenant->subscribe('addon-storage', $storageAddon);

$user->subscribe('main', $personalPlan);   // does not collide with $tenant's 'main'
```

## Multiple subscriber types in one app

You can use the trait on as many models as you want. A common pattern: bill at both the `User` (personal account) and `Tenant` (workspace) level. Both pull in the trait, and the polymorphic `subscriber` relationship on `PlanSubscription` keeps them straight.

## Next

[Create your first plan and features](/documentation/subscriptions/v2/getting-started/create-plan).
