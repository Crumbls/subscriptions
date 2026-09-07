<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Events\SubscriptionCanceled;
use Crumbls\Subscriptions\Events\SubscriptionCreated;
use Crumbls\Subscriptions\Events\SubscriptionPlanChanged;
use Crumbls\Subscriptions\Events\SubscriptionRenewed;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
    $this->plan = Plan::create([
        'name' => 'Pro',
        'price' => 999,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);
});

it('fires SubscriptionCreated when subscribing', function (): void {
    Event::fake(SubscriptionCreated::class);

    $this->user->newPlanSubscription('main', $this->plan);

    Event::assertDispatched(SubscriptionCreated::class, fn($event) => $event->subscription->plan_id === $this->plan->id);
});

it('fires SubscriptionCanceled when canceling', function (): void {
    Event::fake(SubscriptionCanceled::class);

    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel();

    Event::assertDispatched(SubscriptionCanceled::class, fn($event) => $event->immediate === false);
});

it('fires SubscriptionCanceled with immediate flag', function (): void {
    Event::fake(SubscriptionCanceled::class);

    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel(immediately: true);

    Event::assertDispatched(SubscriptionCanceled::class, fn($event) => $event->immediate === true);
});

it('fires SubscriptionRenewed when renewing', function (): void {
    Event::fake(SubscriptionRenewed::class);

    Carbon::setTestNow('2026-03-01');
    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    Carbon::setTestNow('2026-05-01');
    $subscription->renew();

    Event::assertDispatched(SubscriptionRenewed::class);

    Carbon::setTestNow();
});

it('fires SubscriptionPlanChanged when changing plans', function (): void {
    Event::fake(SubscriptionPlanChanged::class);

    $newPlan = Plan::create([
        'name' => 'Enterprise',
        'price' => 4999,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'year',
    ]);

    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->changePlan($newPlan);

    Event::assertDispatched(SubscriptionPlanChanged::class, fn($event) => $event->oldPlan->id === $this->plan->id
        && $event->newPlan->id === $newPlan->id);
});
