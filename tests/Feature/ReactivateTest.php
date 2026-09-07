<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
    $this->plan = Plan::create([
        'name' => 'Pro',
        'price' => 1000,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);
});

it('can reactivate a pending cancellation', function () {
    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel();

    expect($subscription->pendingCancellation())->toBeTrue()
        ->and($subscription->canceled_at)->not->toBeNull();

    $subscription->reactivate();

    expect($subscription->canceled_at)->toBeNull()
        ->and($subscription->cancels_at)->toBeNull()
        ->and($subscription->active())->toBeTrue()
        ->and($subscription->pendingCancellation())->toBeFalse();
});

it('cannot reactivate an ended subscription', function () {
    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel(immediately: true);

    expect(fn () => $subscription->reactivate())->toThrow(LogicException::class);
});

it('reports days until end', function () {
    Carbon::setTestNow('2026-03-01 00:00:00');

    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    // Subscription starts now (no trial), ends ~April 1
    expect($subscription->daysUntilEnd())->toBeGreaterThan(25)
        ->and($subscription->daysUntilEnd())->toBeLessThanOrEqual(31);

    Carbon::setTestNow();
});

it('returns null for daysUntilEnd when already ended', function () {
    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel(immediately: true);

    expect($subscription->daysUntilEnd())->toBeNull();
});

it('reports days until trial end', function () {
    Carbon::setTestNow('2026-03-01 00:00:00');

    $trialPlan = Plan::create([
        'name' => 'Trial',
        'price' => 1000,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'trial_period' => 14,
        'trial_interval' => 'day',
    ]);

    $subscription = $this->user->newPlanSubscription('main', $trialPlan);

    expect($subscription->daysUntilTrialEnd())->toBe(14)
        ->and($subscription->onTrial())->toBeTrue();

    Carbon::setTestNow();
});
