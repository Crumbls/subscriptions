# Crumbls Subscriptions

A flexible plans and subscription management system for Laravel. Manage SaaS plans, features, and subscriber usage tracking without coupling to any payment provider.

> **Inspired by** [rinvex/laravel-subscriptions](https://github.com/rinvex/laravel-subscriptions), which is now abandoned. This package modernizes the codebase for Laravel 11/12/13, PHP 8.3+, and current Laravel conventions.

## Features

- Plan management with trial, grace, and invoice periods
- Standalone features with many-to-many plan relationships
- Per-plan feature values (e.g. Basic gets 5 users, Pro gets 50)
- Feature-based usage tracking with automatic resets
- Polymorphic subscriptions — attach to any model
- Grace period support — subscriptions stay active during grace window
- Lifecycle events — hook into created, canceled, renewed, plan changed
- Translatable plan/feature names and descriptions (via Spatie)
- Sortable plans and features (via Spatie)
- Configurable table names and swappable models
- Route middleware for feature gating
- Artisan command to prune expired subscriptions

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13

## Installation

```bash
composer require crumbls/subscriptions
```

Publish config and migrations (optional):

```bash
php artisan vendor:publish --tag=subscriptions-config
php artisan vendor:publish --tag=subscriptions-migrations
```

Run migrations:

```bash
php artisan migrate
```

Migrations autoload by default. Set `autoload_migrations` to `false` in `config/subscriptions.php` to disable this.

## Database Structure

```
plans                       features
├── id                      ├── id
├── slug                    ├── slug
├── name (json)             ├── name (json)
├── description (json)      ├── description (json)
├── price                   ├── resettable_period
├── signup_fee              ├── resettable_interval
├── currency                ├── sort_order
├── invoice_period          └── timestamps + soft deletes
├── invoice_interval
├── trial_period            plan_features (pivot)
├── trial_interval          ├── id
├── grace_period            ├── plan_id → plans
├── grace_interval          ├── feature_id → features
├── is_active               ├── value
├── active_subscribers_limit├── sort_order
├── sort_order              └── timestamps
└── timestamps + soft deletes

plan_subscriptions          plan_subscription_usage
├── id                      ├── id
├── subscriber_id           ├── subscription_id → plan_subscriptions
├── subscriber_type         ├── feature_id → features
├── plan_id → plans         ├── used
├── slug                    ├── valid_until
├── name (json)             └── timestamps + soft deletes
├── description (json)
├── trial_ends_at
├── starts_at
├── ends_at
├── cancels_at
├── canceled_at
└── timestamps + soft deletes
```

Features are standalone entities. The `value` (limit) is set per-plan on the `plan_features` pivot. This lets a single "Users" feature have different limits across plans.

## Usage

### Add subscriptions to a model

```php
use Crumbls\Subscriptions\Traits\HasPlanSubscriptions;

class Tenant extends Model
{
    use HasPlanSubscriptions;
}
```

Works on any Eloquent model — User, Tenant, Team, Organization, etc.

### Create a plan

```php
use Crumbls\Subscriptions\Models\Plan;

$plan = Plan::create([
    'name' => 'Pro',
    'description' => 'Pro plan',
    'price' => 9.99,
    'signup_fee' => 1.99,
    'invoice_period' => 1,
    'invoice_interval' => 'month',
    'trial_period' => 15,
    'trial_interval' => 'day',
    'grace_period' => 7,
    'grace_interval' => 'day',
    'currency' => 'USD',
]);
```

Intervals accept: `hour`, `day`, `week`, `month`, `year`.

### Create features

Features are standalone — create them once, attach to many plans:

```php
use Crumbls\Subscriptions\Models\Feature;

$users = Feature::create([
    'name' => 'Users',
    'slug' => 'users',
    'resettable_period' => 0, // never resets — running count
]);

$apiCalls = Feature::create([
    'name' => 'API Requests',
    'slug' => 'api-requests',
    'resettable_period' => 1,
    'resettable_interval' => 'month', // resets monthly
]);

$ssl = Feature::create([
    'name' => 'SSL',
    'slug' => 'ssl',
]);
```

### Attach features to plans with values

The `value` is set per-plan on the pivot — this is how different plans get different limits:

```php
// Basic: 5 users, 100 API calls, no SSL
$basic->features()->attach($users, ['value' => '5']);
$basic->features()->attach($apiCalls, ['value' => '100']);

// Pro: 50 users, 10000 API calls, SSL enabled
$pro->features()->attach($users, ['value' => '50']);
$pro->features()->attach($apiCalls, ['value' => '10000']);
$pro->features()->attach($ssl, ['value' => 'true']);

// Enterprise: unlimited users, unlimited API calls, SSL enabled
$enterprise->features()->attach($users, ['value' => '999999']);
$enterprise->features()->attach($apiCalls, ['value' => '999999']);
$enterprise->features()->attach($ssl, ['value' => 'true']);
```

### Subscribe

```php
$tenant->newPlanSubscription('main', $plan);
```

### Check subscription status

```php
$tenant->subscribedTo($plan->id);               // bool
$tenant->hasActiveSubscription();                // any active sub?
$subscription = $tenant->planSubscription('main');
$subscription = $tenant->currentSubscription();  // most recent active

$subscription->active();         // true if not ended, or on trial/grace
$subscription->onTrial();        // currently in trial period
$subscription->onGracePeriod();  // ended but within grace window
$subscription->canceled();       // has been canceled
$subscription->ended();          // period has expired
$subscription->inactive();       // opposite of active
$subscription->pendingCancellation(); // canceled but not yet ended
$subscription->daysUntilEnd();   // int or null
$subscription->daysUntilTrialEnd(); // int or null
```

### Feature usage

```php
$subscription->recordFeatureUsage('api-requests');
$subscription->recordFeatureUsage('api-requests', 5);           // add 5
$subscription->recordFeatureUsage('api-requests', 10, false);   // set to 10
$subscription->reduceFeatureUsage('api-requests', 3);
$subscription->canUseFeature('api-requests');      // bool — checks used < value
$subscription->getFeatureUsage('api-requests');     // int — current usage
$subscription->getFeatureRemainings('api-requests'); // int — remaining quota
$subscription->getFeatureValue('api-requests');     // raw value from pivot
```

#### Enforcing limits (example: user count)

```php
// When adding a user to a tenant
$subscription = $tenant->currentSubscription();

if (!$subscription->canUseFeature('users')) {
    throw new \Exception('User limit reached for your plan.');
}

$subscription->recordFeatureUsage('users');

// When removing a user
$subscription->reduceFeatureUsage('users');
```

### Plan lifecycle

```php
$subscription->changePlan($newPlan);    // switch plans (resets usage if billing cycle changes)
$subscription->renew();                 // start a new period
$subscription->cancel();                // cancel at end of current period
$subscription->cancel(immediately: true); // cancel now
$subscription->reactivate();            // undo pending cancellation
```

### Scopes

```php
use Crumbls\Subscriptions\Models\PlanSubscription;

PlanSubscription::findActive()->get();
PlanSubscription::findEndingPeriod(7)->get();   // ending within 7 days
PlanSubscription::findEndedPeriod()->get();
PlanSubscription::findEndingTrial(3)->get();     // trial ending within 3 days
PlanSubscription::findEndedTrial()->get();
PlanSubscription::ofSubscriber($tenant)->get();
PlanSubscription::byPlanId($plan->id)->get();

Plan::active()->get();
Plan::inactive()->get();
Plan::free()->get();
Plan::paid()->get();
```

### Events

| Event | Fired when |
|---|---|
| `SubscriptionCreated` | A new subscription is created |
| `SubscriptionCanceled` | A subscription is canceled (includes `$immediate` flag) |
| `SubscriptionRenewed` | A subscription is renewed |
| `SubscriptionPlanChanged` | A subscription switches plans (includes `$oldPlan` and `$newPlan`) |

```php
use Crumbls\Subscriptions\Events\SubscriptionCreated;

class SendWelcomeEmail
{
    public function handle(SubscriptionCreated $event): void
    {
        $event->subscription->subscriber->notify(/* ... */);
    }
}
```

### Middleware

Gate routes by feature or subscription:

```php
// Must have the "api-requests" feature available
Route::middleware('can-use-feature:api-requests')->group(/* ... */);

// Check a specific subscription by slug
Route::middleware('can-use-feature:api-requests,pro')->group(/* ... */);

// Must have any active subscription
Route::middleware('subscribed')->group(/* ... */);

// Must be subscribed to a specific plan slug
Route::middleware('subscribed:pro')->group(/* ... */);
```

### Pruning expired subscriptions

```bash
php artisan subscriptions:prune              # soft-deletes canceled subs older than 30 days
php artisan subscriptions:prune --days=90    # custom threshold
php artisan subscriptions:prune --force      # skip confirmation
```

Schedule it:

```php
// routes/console.php or bootstrap/app.php
Schedule::command('subscriptions:prune --force')->daily();
```

## Configuration

Publish the config to customize table names or swap model classes:

```php
// config/subscriptions.php
return [
    'autoload_migrations' => true,
    'broadcast_events' => false,

    'tables' => [
        'plans' => 'plans',
        'features' => 'features',
        'plan_features' => 'plan_features',
        'plan_subscriptions' => 'plan_subscriptions',
        'plan_subscription_usage' => 'plan_subscription_usage',
    ],

    'models' => [
        'plan' => \Crumbls\Subscriptions\Models\Plan::class,
        'feature' => \Crumbls\Subscriptions\Models\Feature::class,
        'plan_feature' => \Crumbls\Subscriptions\Models\PlanFeature::class,
        'plan_subscription' => \Crumbls\Subscriptions\Models\PlanSubscription::class,
        'plan_subscription_usage' => \Crumbls\Subscriptions\Models\PlanSubscriptionUsage::class,
    ],
];
```

### Extending models

Every model is resolved through config. Extend the base model and update the config:

```php
namespace App\Models;

use Crumbls\Subscriptions\Models\Plan as BasePlan;

class Plan extends BasePlan
{
    public function cancel(bool $immediately = false): static
    {
        // Cancel recurring payment with your provider
        $this->subscriber->paymentProvider()->cancelSubscription($this);

        return parent::cancel($immediately);
    }
}
```

```php
// config/subscriptions.php
'models' => [
    'plan' => \App\Models\Plan::class,
],
```

All relationships, scopes, traits, middleware, and the prune command resolve models through config — your custom models are used everywhere automatically.

## Models

| Model | Description |
|---|---|
| `Plan` | A subscription plan with pricing, billing cycle, trial/grace periods |
| `Feature` | A standalone feature (e.g. "Users", "API Calls", "SSL") |
| `PlanFeature` | Pivot model linking features to plans with a `value` per plan |
| `PlanSubscription` | A subscriber's subscription to a plan |
| `PlanSubscriptionUsage` | Tracks how much of a feature a subscription has consumed |

## Considerations

- **Payments are out of scope.** This package handles plan/subscription logic only. Integrate with Stripe, Paddle, etc. separately via events or model overrides.
- **Translatable fields**: `name` and `description` on plans, features, and subscriptions are stored as JSON and support multiple locales via [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable).
- **Soft deletes**: Plans, features, subscriptions, and usage all use soft deletes. The prune command only soft-deletes; use `forceDelete()` if you need permanent removal.
- **Features are standalone**: Create a feature once, attach it to multiple plans with different values. This avoids duplicating feature definitions across plans.

## License

MIT
