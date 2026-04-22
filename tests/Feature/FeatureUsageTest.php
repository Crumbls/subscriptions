<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
    $this->plan = Plan::create([
        'name' => 'Pro',
        'price' => 9.99,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);

    $this->plan->features()->create([
        'name' => 'API Calls',
        'slug' => 'api-calls',
        'resettable_period' => 1,
        'resettable_interval' => 'month',
    ], ['value' => '100']);

    $this->plan->features()->create([
        'name' => 'SSL',
        'slug' => 'ssl',
    ], ['value' => 'true']);

    $this->plan->features()->create([
        'name' => 'Disabled',
        'slug' => 'disabled-feature',
    ], ['value' => 'false']);

    $this->subscription = $this->user->newPlanSubscription('main', $this->plan);
});

it('can record feature usage', function (): void {
    $usage = $this->subscription->recordFeatureUsage('api-calls');

    expect($usage->used)->toBe(1);

    $this->subscription->recordFeatureUsage('api-calls', 5);

    expect($usage->fresh()->used)->toBe(6);
});

it('can record non-incremental usage', function (): void {
    $this->subscription->recordFeatureUsage('api-calls', 10);
    $usage = $this->subscription->recordFeatureUsage('api-calls', 3, incremental: false);

    expect($usage->used)->toBe(3);
});

it('can reduce feature usage', function (): void {
    $this->subscription->recordFeatureUsage('api-calls', 10);
    $usage = $this->subscription->reduceFeatureUsage('api-calls', 3);

    expect($usage->used)->toBe(7);
});

it('does not reduce below zero', function (): void {
    $this->subscription->recordFeatureUsage('api-calls', 2);
    $usage = $this->subscription->reduceFeatureUsage('api-calls', 10);

    expect($usage->used)->toBe(0);
});

it('returns null when reducing unused feature', function (): void {
    expect($this->subscription->reduceFeatureUsage('api-calls'))->toBeNull();
});

it('checks if a countable feature can be used', function (): void {
    expect($this->subscription->canUseFeature('api-calls'))->toBeTrue();

    // Use all available
    $this->subscription->recordFeatureUsage('api-calls', 100);

    expect($this->subscription->canUseFeature('api-calls'))->toBeFalse();
});

it('treats boolean true features as always available', function (): void {
    expect($this->subscription->canUseFeature('ssl'))->toBeTrue();
});

it('treats boolean false features as never available', function (): void {
    expect($this->subscription->canUseFeature('disabled-feature'))->toBeFalse();
});

it('gets feature usage and remainings', function (): void {
    $this->subscription->recordFeatureUsage('api-calls', 30);

    expect($this->subscription->getFeatureUsage('api-calls'))->toBe(30)
        ->and($this->subscription->getFeatureRemainings('api-calls'))->toBe(70);
});

it('gets feature value', function (): void {
    expect($this->subscription->getFeatureValue('api-calls'))->toBe('100')
        ->and($this->subscription->getFeatureValue('nonexistent'))->toBeNull();
});

it('resets usage when period expires', function (): void {
    // valid_until is derived from the subscription's created_at, so freeze
    // time before creating a fresh subscription for deterministic math.
    Carbon::setTestNow('2026-03-01 00:00:00');

    $subscription = $this->user->newPlanSubscription('resets', $this->plan);
    $subscription->recordFeatureUsage('api-calls', 50);

    // Jump past the reset period (one month after created_at = 2026-04-01)
    Carbon::setTestNow('2026-04-02 00:00:00');

    expect($subscription->getFeatureUsage('api-calls'))->toBe(0);

    $usage = $subscription->recordFeatureUsage('api-calls', 5);
    expect($usage->used)->toBe(5);

    Carbon::setTestNow();
});

it('clears usage when subscription is renewed', function (): void {
    $this->subscription->recordFeatureUsage('api-calls', 50);

    expect($this->subscription->usage()->count())->toBe(1);

    // Renew clears usage
    $this->subscription->renew();

    expect($this->subscription->usage()->count())->toBe(0);
});
