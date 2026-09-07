<?php

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

it('checks hasActiveSubscription', function () {
    expect($this->user->hasActiveSubscription())->toBeFalse();

    $this->user->newPlanSubscription('main', $this->plan);

    // Reload relation
    $this->user->load('planSubscriptions');

    expect($this->user->hasActiveSubscription())->toBeTrue();
});

it('gets currentSubscription', function () {
    expect($this->user->currentSubscription())->toBeNull();

    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->currentSubscription()->id)->toBe($subscription->id);
});

it('returns most recent active subscription as current', function () {
    $plan2 = Plan::create([
        'name' => 'Enterprise',
        'price' => 5000,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'year',
    ]);

    $this->user->newPlanSubscription('basic', $this->plan);
    $latest = $this->user->newPlanSubscription('enterprise', $plan2);

    expect($this->user->currentSubscription()->id)->toBe($latest->id);
});
