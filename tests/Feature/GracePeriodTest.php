<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

it('treats subscription as active during grace period', function (): void {
    $plan = Plan::create([
        'name' => 'Grace Plan',
        'price' => 1000,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'grace_period' => 7,
        'grace_interval' => 'day',
    ]);

    Carbon::setTestNow('2026-03-01 00:00:00');
    $subscription = $this->user->newPlanSubscription('main', $plan);

    // Jump to just after the subscription ends (trial=0, so ends ~April 1)
    Carbon::setTestNow('2026-04-02 00:00:00');

    expect($subscription->ended())->toBeTrue()
        ->and($subscription->onGracePeriod())->toBeTrue()
        ->and($subscription->active())->toBeTrue();

    // Jump past grace period (7 days after end)
    Carbon::setTestNow('2026-04-10 00:00:00');

    expect($subscription->onGracePeriod())->toBeFalse()
        ->and($subscription->active())->toBeFalse();

    Carbon::setTestNow();
});

it('returns false for onGracePeriod when plan has no grace', function (): void {
    $plan = Plan::create([
        'name' => 'No Grace',
        'price' => 1000,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);

    Carbon::setTestNow('2026-03-01 00:00:00');
    $subscription = $this->user->newPlanSubscription('main', $plan);

    Carbon::setTestNow('2026-04-02 00:00:00');

    expect($subscription->onGracePeriod())->toBeFalse();

    Carbon::setTestNow();
});

it('does not apply grace period to canceled+ended subscriptions', function (): void {
    $plan = Plan::create([
        'name' => 'Grace Cancel',
        'price' => 1000,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'grace_period' => 7,
        'grace_interval' => 'day',
    ]);

    $subscription = $this->user->newPlanSubscription('main', $plan);
    $subscription->cancel(immediately: true);

    // Even within the would-be grace period, canceled+ended = inactive
    expect($subscription->active())->toBeFalse();
});
