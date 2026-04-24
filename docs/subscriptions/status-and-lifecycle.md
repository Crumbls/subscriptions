---
title: Status and lifecycle
weight: 10
---

## Create

```php
$sub = $tenant->subscribe('main', $proPlan);
$sub = $tenant->subscribe('main', $proPlan, now()->addDay());  // future start
```

On create:

- `starts_at` defaults to now (or the explicit start date you pass)
- `ends_at` is set to `starts_at + invoice_period invoice_interval`
- `trial_ends_at` is set if the plan has a trial period
- `SubscriptionCreated` event fires

## Status checks

```php
$sub->active();               // not ended, OR on trial, OR on grace
$sub->onTrial();              // now <= trial_ends_at
$sub->onGracePeriod();        // ended, but within grace window
$sub->canceled();             // canceled_at is set
$sub->ended();                // now > ends_at
$sub->inactive();             // !active()
$sub->pendingCancellation();  // canceled but ends_at is still in the future
$sub->daysUntilEnd();         // int days remaining, or null if no end date
$sub->daysUntilTrialEnd();    // int days remaining of trial, or null
```

`active()` is the catch-all check: trial, mid-period, and grace all count as active. Past-grace and immediately-canceled count as inactive.

## Change plan

```php
$sub->changePlan($enterprisePlan);
```

Switches the subscription's `plan_id` and resets the billing period from now. Usage rows are reset because feature attachments may differ between plans. Fires `SubscriptionPlanChanged` with `$oldPlan` and `$newPlan` properties.

## Renew

```php
$sub->renew();
```

Advances `starts_at` and `ends_at` by one billing period. Resets the cancel state if any. Fires `SubscriptionRenewed`.

Most apps trigger this from a payment-provider webhook (Stripe `invoice.paid`, Paddle `subscription_payment_succeeded`, etc.) -- the package never auto-renews.

## Cancel

```php
$sub->cancel();                       // cancel at end of current period
$sub->cancel(immediately: true);      // cancel right now
```

Default cancel sets `canceled_at = now()` but leaves `ends_at` alone -- the subscription stays active until its current period runs out. `pendingCancellation()` returns true during this window.

`immediately: true` sets both `canceled_at` and `ends_at` to now. The subscription drops out of `active()` immediately (no grace).

Both forms fire `SubscriptionCanceled` with an `$immediate` flag on the event.

## Reactivate

```php
$sub->reactivate();
```

Undoes a pending cancellation. Only works while the subscription is still active (i.e. `ends_at` is in the future). After expiration, you have to `renew()` instead.

## Polymorphic subscriber

The subscriber relationship is polymorphic, so any of these work:

```php
$sub->subscriber;   // returns the User, Tenant, Team, whatever
get_class($sub->subscriber);
```

If you query subscriptions independently of a subscriber, scope by type with `PlanSubscription::ofSubscriber($model)`.
