---
title: Events
weight: 80
---

Lifecycle events for hooking payment workflows, sending emails, syncing analytics, or any other side effect that should happen when a subscription changes state.

## Event catalog

| Event | Class | Fired when |
|---|---|---|
| Created | `Crumbls\Subscriptions\Events\SubscriptionCreated` | `$model->subscribe(...)` succeeds |
| Canceled | `Crumbls\Subscriptions\Events\SubscriptionCanceled` | `$sub->cancel()` succeeds. Carries `bool $immediate` |
| Renewed | `Crumbls\Subscriptions\Events\SubscriptionRenewed` | `$sub->renew()` succeeds |
| Plan changed | `Crumbls\Subscriptions\Events\SubscriptionPlanChanged` | `$sub->changePlan(...)` succeeds. Carries `$oldPlan` and `$newPlan` |

All events expose `$subscription` as a public readonly property.

## Wiring listeners

```php
// app/Providers/EventServiceProvider.php (or AppServiceProvider boot)
use Crumbls\Subscriptions\Events\SubscriptionCreated;
use Crumbls\Subscriptions\Events\SubscriptionCanceled;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(SubscriptionCreated::class, SendWelcomeEmail::class);
    Event::listen(SubscriptionCanceled::class, NotifyAccountManager::class);
}
```

## Common patterns

### Welcome email on first subscription

```php
class SendWelcomeEmail
{
    public function handle(SubscriptionCreated $event): void
    {
        $event->subscription->subscriber->notify(
            new SubscriptionStarted($event->subscription)
        );
    }
}
```

### Cancel the recurring payment when the subscription is canceled

```php
class CancelStripeSubscription
{
    public function handle(SubscriptionCanceled $event): void
    {
        $stripeId = $event->subscription->subscriber->stripe_subscription_id;

        if ($stripeId) {
            Stripe::cancel($stripeId, immediately: $event->immediate);
        }
    }
}
```

### Track plan upgrades and downgrades

```php
class TrackPlanChange
{
    public function handle(SubscriptionPlanChanged $event): void
    {
        $direction = $event->newPlan->price > $event->oldPlan->price
            ? 'upgrade'
            : 'downgrade';

        analytics()->track('plan_changed', [
            'subscriber'  => $event->subscription->subscriber_id,
            'old_plan'    => $event->oldPlan->slug,
            'new_plan'    => $event->newPlan->slug,
            'direction'   => $direction,
        ]);
    }
}
```

## Broadcasting

These events are plain PHP / Laravel events -- they do not implement `ShouldBroadcast` out of the box. If you want to broadcast, extend the event in your application:

```php
namespace App\Events;

use Crumbls\Subscriptions\Events\SubscriptionCanceled as BaseEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SubscriptionCanceled extends BaseEvent implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->subscription->subscriber_id)];
    }
}
```

Then dispatch your version from a listener on the base event, or override the model methods to dispatch yours instead.
