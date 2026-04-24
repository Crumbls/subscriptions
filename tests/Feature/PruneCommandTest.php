<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
    $this->plan = Plan::create([
        'name' => 'Pro',
        'price' => 10,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);
});

it('prunes old canceled subscriptions', function (): void {
    Carbon::setTestNow('2026-01-01 00:00:00');
    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel(immediately: true);

    Carbon::setTestNow('2026-03-01 00:00:00');

    $this->artisan('subscriptions:prune', ['--force' => true])
        ->expectsOutputToContain('Pruned 1')
        ->assertSuccessful();

    expect(PlanSubscription::withTrashed()->find($subscription->id)->deleted_at)->not->toBeNull();
});

it('does not prune active subscriptions', function (): void {
    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    $this->artisan('subscriptions:prune', ['--force' => true])
        ->expectsOutputToContain('No expired')
        ->assertSuccessful();

    expect($subscription->fresh()->deleted_at)->toBeNull();
});

it('respects the days option', function (): void {
    Carbon::setTestNow('2026-01-15 00:00:00');
    $subscription = $this->user->newPlanSubscription('main', $this->plan);
    $subscription->cancel(immediately: true);

    // Only 10 days later — default 30-day threshold not met
    Carbon::setTestNow('2026-01-25 00:00:00');

    $this->artisan('subscriptions:prune', ['--force' => true, '--days' => 30])
        ->expectsOutputToContain('No expired')
        ->assertSuccessful();

    // With a 5-day threshold, it should prune
    $this->artisan('subscriptions:prune', ['--force' => true, '--days' => 5])
        ->expectsOutputToContain('Pruned 1')
        ->assertSuccessful();

    Carbon::setTestNow();
});

it('prunes naturally expired subscriptions (never canceled)', function (): void {
    Carbon::setTestNow('2026-01-01 00:00:00');
    $subscription = $this->user->newPlanSubscription('main', $this->plan);

    // Plan has a 1-month invoice period, so sub ends 2026-02-01. Fast-forward past
    // the 30-day pruning threshold beyond that.
    Carbon::setTestNow('2026-04-01 00:00:00');

    $this->artisan('subscriptions:prune', ['--force' => true])
        ->expectsOutputToContain('Pruned 1')
        ->assertSuccessful();

    expect(PlanSubscription::withTrashed()->find($subscription->id)->deleted_at)->not->toBeNull();

    Carbon::setTestNow();
});
