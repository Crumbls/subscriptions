<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
    $this->plan = Plan::create([
        'name' => 'Pro',
        'price' => 999,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'trial_period' => 7,
        'trial_interval' => 'day',
    ]);
});

it('can subscribe a user to a plan', function (): void {
    Carbon::setTestNow('2026-03-01 00:00:00');

    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    expect($subscription)->toBeInstanceOf(PlanSubscription::class)
        ->and($subscription->plan_id)->toBe($this->plan->id)
        ->and($subscription->subscriber_id)->toBe($this->user->id)
        ->and($subscription->subscriber_type)->toBe($this->user->getMorphClass())
        ->and($subscription->active())->toBeTrue()
        ->and($subscription->onTrial())->toBeTrue();

    Carbon::setTestNow();
});

it('can check if user is subscribed to a plan', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->subscribedTo($this->plan->id))->toBeTrue()
        ->and($this->user->subscribedTo(999))->toBeFalse();
});

it('can retrieve active plan subscriptions', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->activePlanSubscriptions())->toHaveCount(1);
});

it('can retrieve subscribed plans', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    $plans = $this->user->subscribedPlans();
    expect($plans)->toHaveCount(1)
        ->and($plans->first()->id)->toBe($this->plan->id);
});

it('can cancel a subscription at period end', function (): void {
    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    $subscription->cancel();

    expect($subscription->canceled())->toBeTrue()
        ->and($subscription->active())->toBeTrue(); // still active until period ends
});

it('can cancel a subscription immediately', function (): void {
    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    $subscription->cancel(immediately: true);

    expect($subscription->canceled())->toBeTrue()
        ->and($subscription->ended())->toBeTrue()
        ->and($subscription->active())->toBeFalse();
});

it('can renew a subscription', function (): void {
    Carbon::setTestNow('2026-03-01 00:00:00');

    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $originalEnd = $subscription->ends_at->copy();

    // Fast forward well past end date (trial + 1 month)
    Carbon::setTestNow('2026-05-01 00:00:00');
    expect($subscription->ended())->toBeTrue();

    $subscription->renew();

    expect($subscription->ends_at->gt($originalEnd))->toBeTrue()
        ->and($subscription->canceled_at)->toBeNull();

    Carbon::setTestNow();
});

it('cannot renew a canceled ended subscription', function (): void {
    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel(immediately: true);

    expect(fn () => $subscription->renew())->toThrow(LogicException::class);
});

it('can change plans', function (): void {
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

    expect($subscription->plan_id)->toBe($newPlan->id);
});

it('detects ended and active states correctly', function (): void {
    Carbon::setTestNow('2026-03-01 00:00:00');

    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    expect($subscription->active())->toBeTrue()
        ->and($subscription->ended())->toBeFalse()
        ->and($subscription->inactive())->toBeFalse();

    // Jump past end date and trial
    Carbon::setTestNow('2026-05-01 00:00:00');

    expect($subscription->ended())->toBeTrue()
        ->and($subscription->active())->toBeFalse()
        ->and($subscription->inactive())->toBeTrue();

    Carbon::setTestNow();
});

it('scopes active subscriptions', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect(PlanSubscription::findActive()->count())->toBe(1)
        ->and(PlanSubscription::findEndedPeriod()->count())->toBe(0);
});

it('scopes by subscriber', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);
    $other = User::create(['name' => 'Other', 'email' => 'other@example.com']);

    expect(PlanSubscription::ofSubscriber($this->user)->count())->toBe(1)
        ->and(PlanSubscription::ofSubscriber($other)->count())->toBe(0);
});
