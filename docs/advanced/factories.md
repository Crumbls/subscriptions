---
title: Factories
weight: 20
---

Every model ships with a factory under `Crumbls\Subscriptions\Database\Factories`. Use them in tests and seeders.

## Plan factories

```php
use Crumbls\Subscriptions\Models\Plan;

Plan::factory()->create();                          // a generic paid monthly plan
Plan::factory()->free()->create();                  // price = 0
Plan::factory()->paid()->create();                  // price > 0
Plan::factory()->withTrial(14)->create();           // 14-day trial
Plan::factory()->withGrace(7)->create();            // 7-day grace
Plan::factory()->limitedTo(100)->create();          // active_subscribers_limit = 100
Plan::factory()->inactive()->create();              // is_active = false
```

States compose:

```php
Plan::factory()
    ->paid()
    ->withTrial(30)
    ->withGrace(14)
    ->limitedTo(50)
    ->create();
```

## Feature factories

```php
use Crumbls\Subscriptions\Models\Feature;

Feature::factory()->create();                       // generic feature, no resets
Feature::factory()->resettableMonthly()->create();  // resettable_period=1, resettable_interval=month
Feature::factory()->resettableDaily()->create();
```

## Subscription factories

```php
use Crumbls\Subscriptions\Models\PlanSubscription;

PlanSubscription::factory()->for($user, 'subscriber')->create();

PlanSubscription::factory()
    ->for($user, 'subscriber')
    ->ended()
    ->canceled()
    ->create();
```

The polymorphic `for($model, 'subscriber')` is the standard Laravel way to set the polymorphic relation -- the second argument is the relationship name.

## Patterns

### Seed three plans for a fresh dev DB

```php
// database/seeders/PlanSeeder.php
public function run(): void
{
    $users    = Feature::factory()->create(['name' => 'Users', 'slug' => 'users']);
    $apiCalls = Feature::factory()->resettableMonthly()->create([
        'name' => 'API Calls', 'slug' => 'api-requests',
    ]);

    $basic = Plan::factory()->paid()->create([
        'name' => 'Basic', 'price' => 9.99,
    ]);
    $basic->features()->attach($users,    ['value' => '5']);
    $basic->features()->attach($apiCalls, ['value' => '1000']);

    $pro = Plan::factory()->paid()->withTrial(14)->create([
        'name' => 'Pro', 'price' => 49.99,
    ]);
    $pro->features()->attach($users,    ['value' => '50']);
    $pro->features()->attach($apiCalls, ['value' => '50000']);
}
```

### Test a feature gate

```php
test('user cannot exceed user limit', function () {
    $tenant = Tenant::factory()->create();
    $plan   = Plan::factory()->paid()->create();
    $users  = Feature::factory()->create(['slug' => 'users']);
    $plan->features()->attach($users, ['value' => '2']);

    $sub = $tenant->subscribe('main', $plan);
    $sub->recordFeatureUsage('users', 2);

    expect($sub->canUseFeature('users'))->toBeFalse();
});
```
